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
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
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
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Validation\Event\MigrationPostValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Event\MigrationPreValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidOptionalFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidRequiredFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidRequiredTranslation;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationMissingRequiredFieldLog;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use function var_dump;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MigrationValidationService implements ResetInterface
{
    /**
     * Maps entity name to an associative array of required field property names.
     *
     * Example:
     * [
     *    'entity_name' => [
     *       'required_field_name' => true,
     *    ],
     * ]
     *
     * @var array<string, array<string, true>>
     */
    private array $requiredDefinitionFieldsCache = [];

    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggingServiceInterface $loggingService,
        private readonly Connection $connection,
    ) {
    }

    public function reset(): void
    {
        $this->requiredDefinitionFieldsCache = [];
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
     * Validates that all required fields are present.
     * Required fields are determined by checking which database columns are non-nullable without a default value
     */
    private function validateEntityStructure(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();

        $fields = $entityDefinition->getFields();
        $entityName = $entityDefinition->getEntityName();

        $convertedData = $validationContext->getConvertedData();
        $validationResult = $validationContext->getValidationResult();

        $requiredFields = $this->getRequiredFields(
            $fields,
            $entityName
        );

        $convertedFieldNames = array_keys($convertedData);
        $missingRequiredFields = array_diff(
            array_keys($requiredFields),
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
    }

    /**
     * Validates that all field values conform to their field definitions by attempting to serialize them.
     */
    private function validateFields(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();
        $fields = $entityDefinition->getFields();
        $entityName = $entityDefinition->getEntityName();

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

        $requiredFields = $this->getRequiredFields($fields, $entityName);

        foreach ($convertedData as $fieldName => $value) {
            if (!$fields->has($fieldName)) {
                continue;
            }

            $field = clone $fields->get($fieldName);
            $isRequired = isset($requiredFields[$fieldName]);

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
                $logClass = $isRequired
                    ? MigrationValidationInvalidRequiredFieldValueLog::class
                    : MigrationValidationInvalidOptionalFieldValueLog::class;

                if ($field instanceof TranslationsAssociationField) {
                    $logClass = MigrationValidationInvalidRequiredTranslation::class;
                }

                $validationResult->addLog(
                    MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                        ->withEntityName($entityDefinition->getEntityName())
                        ->withFieldName($fieldName)
                        ->withConvertedData([$fieldName => $value])
                        ->withSourceData($validationContext->getSourceData())
                        ->withExceptionMessage($e->getMessage())
                        ->withExceptionTrace($e->getTrace())
                        ->withEntityId($id)
                        ->build($logClass)
                );
            }
        }
    }

    /**
     * Gets the map of required field property names for the given entity and caches the result for future calls.
     *
     * A field is considered required if:
     * - It has the Required flag in the entity definition, AND
     * - It's either not StorageAware (no direct database column), OR its database column is non-nullable without a default value
     *
     * @return array<string, true>
     */
    private function getRequiredFields(CompiledFieldCollection $fields, string $entityName): array
    {
        if (isset($this->requiredDefinitionFieldsCache[$entityName])) {
            return $this->requiredDefinitionFieldsCache[$entityName];
        }

        $requiredDbColumns = $this->getRequiredDatabaseColumns($entityName);
        $requiredFields = [];

        foreach ($fields->filterByFlag(Required::class) as $field) {
            if (!($field instanceof StorageAware)) {
                $requiredFields[$field->getPropertyName()] = true;

                continue;
            }

            if (isset($requiredDbColumns[$field->getStorageName()])) {
                $requiredFields[$field->getPropertyName()] = true;
            }
        }

        $this->requiredDefinitionFieldsCache[$entityName] = $requiredFields;

        return $requiredFields;
    }

    /**
     * Gets the map of required database columns for the given entity.
     * A required database column is defined as a column that is non-nullable, has no default value, and is not auto-incrementing.
     *
     * @return array<string, true>
     */
    private function getRequiredDatabaseColumns(string $entityName): array
    {
        $requiredColumns = [];

        $columns = $this->connection
            ->createSchemaManager()
            ->listTableColumns($entityName);

        foreach ($columns as $column) {
            if ($column->getNotnull() && $column->getDefault() === null && !$column->getAutoincrement()) {
                $requiredColumns[$column->getName()] = true;
            }
        }

        return $requiredColumns;
    }
}
