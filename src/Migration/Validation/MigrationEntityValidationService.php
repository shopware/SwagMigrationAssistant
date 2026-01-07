<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\CompiledFieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Validation\Event\MigrationPostValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Event\MigrationPreValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidAssociationLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidOptionalFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidRequiredFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidRequiredTranslation;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationMissingRequiredFieldLog;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MigrationEntityValidationService implements ResetInterface
{
    /**
     * @var list<class-string<Field>>
     */
    private const SYSTEM_MANAGED_FIELDS = [
        CreatedAtField::class,
        UpdatedAtField::class,
        VersionField::class,
        ReferenceVersionField::class,
        TranslationsAssociationField::class,
    ];

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
        private readonly MigrationFieldValidationService $fieldValidationService,
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
     *
     * @throws \Exception
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

        if (!$this->definitionRegistry->has($entityName)) {
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
            $this->validateFieldValues($validationContext);
        } catch (\Throwable $exception) {
            $this->addExceptionLog($validationContext, $exception);
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
     * @throws \Exception|Exception
     */
    private function validateEntityStructure(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();

        $requiredFields = $this->getRequiredFields(
            $entityDefinition->getFields(),
            $entityDefinition->getEntityName()
        );

        $missingRequiredFields = array_diff(
            array_keys($requiredFields),
            array_keys($validationContext->getConvertedData())
        );

        foreach ($missingRequiredFields as $missingField) {
            $this->addMissingRequiredFieldLog($validationContext, $missingField);
        }
    }

    /**
     * @throws \Exception|Exception
     */
    private function validateFieldValues(MigrationValidationContext $validationContext): void
    {
        $convertedData = $validationContext->getConvertedData();
        $id = $convertedData['id'] ?? null;

        if (!$this->validateId($validationContext, $id)) {
            return;
        }

        $entityDefinition = $validationContext->getEntityDefinition();
        $entityName = $entityDefinition->getEntityName();

        $fields = $entityDefinition->getFields();
        $requiredFields = $this->getRequiredFields($fields, $entityName);

        foreach ($convertedData as $fieldName => $value) {
            try {
                $this->fieldValidationService->validateField(
                    $entityName,
                    $fieldName,
                    $value,
                    $validationContext->getContext(),
                    isset($requiredFields[$fieldName])
                );
            } catch (MigrationValidationException $exception) {
                $this->addValidationExceptionLog($validationContext, $exception, $fieldName, $value, (string) $id);
            } catch (\Throwable $exception) {
                $this->addExceptionLog($validationContext, $exception);
            }
        }
    }

    private function validateId(MigrationValidationContext $validationContext, mixed $id): bool
    {
        if ($id === null) {
            $this->addExceptionLog(
                $validationContext,
                MigrationValidationException::unexpectedNullValue('id')
            );

            return false;
        }

        if (!\is_string($id) || !Uuid::isValid($id)) {
            $this->addExceptionLog(
                $validationContext,
                MigrationValidationException::invalidId((string) $id, $validationContext->getEntityDefinition()->getEntityName())
            );

            return false;
        }

        return true;
    }

    /**
     * @throws Exception
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
            if (\in_array($field::class, self::SYSTEM_MANAGED_FIELDS, true)) {
                continue;
            }

            if (!($field instanceof StorageAware)) {
                $requiredFields[$field->getPropertyName()] = true;

                continue;
            }

            if (isset($requiredDbColumns[$field->getStorageName()])) {
                $requiredFields[$field->getPropertyName()] = true;
            }
        }

        return $this->requiredDefinitionFieldsCache[$entityName] = $requiredFields;
    }

    /**
     * @throws Exception
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

    private function addMissingRequiredFieldLog(MigrationValidationContext $validationContext, string $fieldName): void
    {
        $convertedData = $validationContext->getConvertedData();
        $entityId = isset($convertedData['id']) ? (string) $convertedData['id'] : null;

        $validationContext->getValidationResult()->addLog(
            MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
                ->withFieldName($fieldName)
                ->withConvertedData($convertedData)
                ->withEntityId($entityId)
                ->build(MigrationValidationMissingRequiredFieldLog::class)
        );
    }

    private function addValidationExceptionLog(
        MigrationValidationContext $validationContext,
        MigrationValidationException $exception,
        string $fieldName,
        mixed $value,
        string $entityId,
    ): void {
        $logClass = match ($exception->getErrorCode()) {
            MigrationValidationException::VALIDATION_INVALID_ASSOCIATION => MigrationValidationInvalidAssociationLog::class,
            MigrationValidationException::VALIDATION_INVALID_REQUIRED_FIELD_VALUE => MigrationValidationInvalidRequiredFieldValueLog::class,
            MigrationValidationException::VALIDATION_INVALID_OPTIONAL_FIELD_VALUE => MigrationValidationInvalidOptionalFieldValueLog::class,
            MigrationValidationException::VALIDATION_INVALID_TRANSLATION => MigrationValidationInvalidRequiredTranslation::class,
            default => MigrationValidationExceptionLog::class,
        };

        $validationContext->getValidationResult()->addLog(
            MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
                ->withFieldName($fieldName)
                ->withConvertedData([$fieldName => $value])
                ->withSourceData($validationContext->getSourceData())
                ->withExceptionMessage($exception->getMessage())
                ->withExceptionTrace($exception->getTrace())
                ->withEntityId($entityId)
                ->build($logClass)
        );
    }

    private function addExceptionLog(MigrationValidationContext $validationContext, \Throwable $exception): void
    {
        $convertedData = $validationContext->getConvertedData();
        $entityId = isset($convertedData['id']) ? (string) $convertedData['id'] : null;

        $validationContext->getValidationResult()->addLog(
            MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
                ->withSourceData($validationContext->getSourceData())
                ->withConvertedData($convertedData)
                ->withExceptionMessage($exception->getMessage())
                ->withExceptionTrace($exception->getTrace())
                ->withEntityId($entityId)
                ->build(MigrationValidationExceptionLog::class)
        );
    }
}
