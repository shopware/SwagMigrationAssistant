<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\CompiledFieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Validation\Event\MigrationPostValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Event\MigrationPreValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidForeignKeyLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationMissingRequiredFieldLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationUnexpectedFieldLog;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MigrationValidationService implements ResetInterface
{
    /**
     * @var array<string, list<string>>
     */
    private array $requiredColumnsCache = [];

    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggingServiceInterface $loggingService,
        private readonly MappingServiceInterface $mappingService,
        private readonly Connection $connection,
    ) {
    }

    public function reset(): void
    {
        $this->requiredColumnsCache = [];
    }

    /**
     * @param array<string, mixed>|null $convertedEntity
     * @param array<string, mixed> $sourceData
     */
    public function validate(
        MigrationContextInterface $migrationContext,
        Context $shopwareContext,
        ?array $convertedEntity,
        string $entityName,
        array $sourceData,
    ): ?MigrationValidationResult {
        if (empty($convertedEntity)) {
            return null;
        }

        $entityDefinition = $this->definitionRegistry->getByEntityName($entityName);

        $validationContext = new MigrationValidationContext(
            $shopwareContext,
            $migrationContext,
            $entityDefinition,
            $convertedEntity,
            $sourceData,
        );

        $this->eventDispatcher->dispatch(
            new MigrationPreValidationEvent($validationContext),
        );

        try {
            $this->validateEntityStructure($validationContext);
            $this->validateFields($validationContext);
            $this->validateAssociations($validationContext);
        } catch (\Throwable $exception) {
            $validationContext->getValidationResult()->addLog(
                MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                    ->withEntityName($entityDefinition->getEntityName())
                    ->withSourceData($validationContext->getSourceData())
                    ->withConvertedData($validationContext->getConvertedData())
                    ->withExceptionMessage($exception->getMessage())
                    ->withExceptionTrace($exception->getTrace())
                    ->withEntityId($convertedEntity['id'] ?? null)
                    ->build(MigrationValidationExceptionLog::class)
            );
        }

        $this->eventDispatcher->dispatch(
            new MigrationPostValidationEvent($validationContext),
        );

        foreach ($validationContext->getValidationResult()->getLogs() as $log) {
            $this->loggingService->addLogEntry($log);
        }

        $this->loggingService->saveLogging($validationContext->getContext());

        return $validationContext->getValidationResult();
    }

    /**
     * Validates that all required fields are present and that no unexpected fields exist.
     * Required fields are determined by checking which database columns are non-nullable without a default value
     */
    private function validateEntityStructure(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();

        $fields = $entityDefinition->getFields();
        $entityName = $entityDefinition->getEntityName();

        $convertedData = $validationContext->getConvertedData();
        $validationResult = $validationContext->getValidationResult();

        $requiredDatabaseColumns = $this->getRequiredDatabaseColumns($entityName);
        $requiredFields = $this->filterRequiredFields(
            $fields,
            $requiredDatabaseColumns
        );

        $convertedFieldNames = array_keys($convertedData);
        $missingRequiredFields = array_diff(
            $requiredFields,
            $convertedFieldNames
        );

        foreach ($missingRequiredFields as $missingField) {
            $validationResult->addLog(
                MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                    ->withEntityName($entityName)
                    ->withFieldName($missingField)
                    ->withConvertedData($convertedData)
                    ->withEntityId($convertedData['id'] ?? null)
                    ->build(MigrationValidationMissingRequiredFieldLog::class)
            );
        }

        $unexpectedFields = array_diff($convertedFieldNames, array_keys($fields->getElements()));

        foreach ($unexpectedFields as $unexpectedField) {
            $validationResult->addLog(
                MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                    ->withEntityName($entityName)
                    ->withFieldName($unexpectedField)
                    ->withConvertedData($convertedData)
                    ->withEntityId($convertedData['id'] ?? null)
                    ->build(MigrationValidationUnexpectedFieldLog::class)
            );
        }
    }

    /**
     * Validates that all field values conform to their field definitions by attempting to serialize them.
     */
    private function validateFields(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();
        $fields = $entityDefinition->getFields();

        $convertedData = $validationContext->getConvertedData();
        $validationResult = $validationContext->getValidationResult();

        $id = $convertedData['id'] ?? null;

        if ($id === null) {
            throw MigrationException::unexpectedNullValue('id');
        }

        if (!Uuid::isValid($id)) {
            throw MigrationException::invalidId($id, $entityDefinition->getEntityName());
        }

        $entityExistence = EntityExistence::createForEntity(
            $entityDefinition->getEntityName(),
            ['id' => $id],
        );

        $parameters = new WriteParameterBag(
            $entityDefinition,
            WriteContext::createFromContext($validationContext->getContext()),
            '',
            new WriteCommandQueue(),
        );

        foreach ($convertedData as $fieldName => $value) {
            if (!$fields->has($fieldName)) {
                continue;
            }

            $field = clone $fields->get($fieldName);

            /**
             * The required flag controls flow in AbstractFieldSerializer::requiresValidation().
             * Without it, the serializer will skip validation for the field.
             */
            $field->setFlags(new Required());

            $keyValue = new KeyValuePair(
                $field->getPropertyName(),
                $value,
                true
            );

            try {
                $serializer = $field->getSerializer();
                \iterator_to_array($serializer->encode($field, $entityExistence, $keyValue, $parameters), false);
            } catch (\Throwable $e) {
                $validationResult->addLog(
                    MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                        ->withEntityName($entityDefinition->getEntityName())
                        ->withFieldName($fieldName)
                        ->withConvertedData([$fieldName => $value])
                        ->withSourceData($validationContext->getSourceData())
                        ->withExceptionMessage($e->getMessage())
                        ->withExceptionTrace($e->getTrace())
                        ->withEntityId($id)
                        ->build(MigrationValidationInvalidFieldValueLog::class)
                );
            }
        }
    }

    /**
     * Validates that all foreign key fields reference existing entities by checking the mapping service.
     */
    private function validateAssociations(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();
        $fkFields = $entityDefinition->getFields()->filterInstance(FkField::class);

        $convertedData = $validationContext->getConvertedData();
        $validationResult = $validationContext->getValidationResult();

        /** @var FkField $fkField */
        foreach ($fkFields as $fkField) {
            $fkFieldName = $fkField->getPropertyName();
            $fkValue = $convertedData[$fkFieldName] ?? null;

            if ($fkValue === null || $fkValue === '') {
                continue;
            }

            $referenceEntity = $fkField->getReferenceEntity();

            if (!$referenceEntity) {
                throw MigrationException::unexpectedNullValue($fkFieldName);
            }

            $hasMapping = $this->mappingService->hasValidMappingByEntityId(
                $validationContext->getMigrationContext()->getConnection()->getId(),
                $referenceEntity,
                $fkValue,
                $validationContext->getContext()
            );

            if (!$hasMapping) {
                $validationResult->addLog(
                    MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                        ->withEntityName($entityDefinition->getEntityName())
                        ->withFieldName($fkFieldName)
                        ->withConvertedData([$fkFieldName => $fkValue])
                        ->withSourceData($validationContext->getSourceData())
                        ->withEntityId($convertedData['id'] ?? null)
                        ->build(MigrationValidationInvalidForeignKeyLog::class)
                );
            }
        }
    }

    /**
     * @param array<string> $requiredDbColumns
     *
     * @return array<string>
     */
    private function filterRequiredFields(CompiledFieldCollection $fields, array $requiredDbColumns): array
    {
        $requiredFields = [];

        foreach ($fields->filterByFlag(Required::class) as $field) {
            if (!($field instanceof StorageAware)) {
                $requiredFields[] = $field->getPropertyName();

                continue;
            }

            if (!\in_array($field->getStorageName(), $requiredDbColumns, true)) {
                continue;
            }

            $requiredFields[] = $field->getPropertyName();
        }

        return $requiredFields;
    }

    /**
     * Gets the list of required database columns for the given entity and caches the result for future calls.
     * A required database column is defined as a column that is non-nullable, has no default value, and is not auto-incrementing.
     *
     * @return list<string>
     */
    private function getRequiredDatabaseColumns(string $entityName): array
    {
        if (isset($this->requiredColumnsCache[$entityName])) {
            return $this->requiredColumnsCache[$entityName];
        }

        $this->requiredColumnsCache[$entityName] = [];

        $columns = $this->connection
            ->createSchemaManager()
            ->listTableColumns($entityName);

        foreach ($columns as $column) {
            if ($column->getNotnull() && $column->getDefault() === null && !$column->getAutoincrement()) {
                $this->requiredColumnsCache[$entityName][] = $column->getName();
            }
        }

        return $this->requiredColumnsCache[$entityName];
    }
}
