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
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
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
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationAssociationInvalidLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationOptionalFieldValueInvalidLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationRequiredFieldMissingLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationRequiredFieldValueInvalidLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationRequiredTranslationInvalidLog;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MigrationEntityValidationService implements ResetInterface
{
    /**
     * System managed fields that are managed by Shopware and should not be validated as required fields
     *
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
     * example:
     * [
     *    'entity_name' => [
     *       'required_field_name' => true,
     *    ],
     * ]
     *
     * @var array<string, array<string, true>>
     */
    private array $requiredDefinitionFieldsCache = [];

    /**
     * Maps entity name to an associative array of storage name to association property name.
     *
     * @var array<string, array<string, string>>
     */
    private array $storageToAssociationCache = [];

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
        $this->storageToAssociationCache = [];
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
            // silently skip validation for empty entities as this is not a validation failure
            // and indicates the data converter chose not to convert this entity
            return null;
        }

        if (!$this->definitionRegistry->has($entityName)) {
            // silently skip validation for unknown entities as the entity is not available
            // in the target system and this is not a validation failure
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
            $this->validateRootEntityFields($validationContext);
        } catch (\Throwable $exception) {
            $this->addExceptionLog($validationContext, $exception);
        }

        $this->eventDispatcher->dispatch(
            new MigrationPostValidationEvent($validationContext),
        );

        foreach ($validationContext->getValidationResult()->getLogs() as $log) {
            $this->loggingService->log($log);
        }

        return $validationContext->getValidationResult();
    }

    /**
     * Validates that all required fields are present in the converted data.
     */
    private function validateEntityStructure(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();
        $entityFields = $entityDefinition->getFields();

        $requiredFields = $this->getRequiredFields(
            $entityFields,
            $entityDefinition
        );

        $convertedData = $validationContext->getConvertedData();

        $missingRequiredFields = array_diff(
            array_keys($requiredFields),
            array_keys($convertedData)
        );

        $missingRequiredFields = $this->filterSatisfiedFkFields(
            $missingRequiredFields,
            $entityFields,
            $convertedData,
            $entityDefinition->getEntityName()
        );

        foreach ($missingRequiredFields as $missingField) {
            $this->addMissingRequiredFieldLog($validationContext, $missingField);
        }
    }

    /**
     * Filters out FK fields that are satisfied by their corresponding association containing an id.
     *
     * When a ManyToOne association (e.g. "group") is provided with an "id" in the converted data,
     * the DAL will automatically resolve this to the FK field (e.g. "groupId").
     * Therefore, we should not report the FK field as missing.
     *
     * @param array<string> $missingFields
     * @param array<string, mixed> $convertedData
     *
     * @return list<string>
     */
    private function filterSatisfiedFkFields(array $missingFields, CompiledFieldCollection $fields, array $convertedData, string $entityName): array
    {
        $storageToAssociation = $this->getStorageToAssociationMapping($entityName, $fields);

        return array_values(array_filter($missingFields, function (string $fieldName) use ($fields, $convertedData, $storageToAssociation): bool {
            $field = $fields->get($fieldName);

            if (!$field instanceof FkField) {
                return true;
            }

            if (!isset($storageToAssociation[$field->getStorageName()])) {
                return true;
            }

            $associationName = $storageToAssociation[$field->getStorageName()];

            if (isset($convertedData[$associationName]['id'])) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @return array<string, string>
     */
    private function getStorageToAssociationMapping(string $entityName, CompiledFieldCollection $fields): array
    {
        if (isset($this->storageToAssociationCache[$entityName])) {
            return $this->storageToAssociationCache[$entityName];
        }

        $mapping = [];

        foreach ($fields as $field) {
            if ($field instanceof ManyToOneAssociationField || $field instanceof OneToOneAssociationField) {
                $mapping[$field->getStorageName()] = $field->getPropertyName();
            }
        }

        return $this->storageToAssociationCache[$entityName] = $mapping;
    }

    /**
     * Validates all fields of the root entity, including nested associations.
     */
    private function validateRootEntityFields(MigrationValidationContext $validationContext): void
    {
        $convertedData = $validationContext->getConvertedData();
        $id = $convertedData['id'] ?? null;

        $entityDefinition = $validationContext->getEntityDefinition();
        $entityName = $entityDefinition->getEntityName();

        if (!$this->validateId($validationContext, $entityName, $id)) {
            return;
        }

        $fields = $entityDefinition->getFields();
        $requiredFields = $this->getRequiredFields($fields, $entityDefinition);

        foreach ($convertedData as $fieldName => $value) {
            $field = $fields->get($fieldName);

            // recursively validate nested association entities
            if ($field !== null && $value !== null) {
                $this->validateNestedAssociations($validationContext, $field, $fieldName, $value);
            }

            try {
                $this->fieldValidationService->validateField(
                    $entityName,
                    $fieldName,
                    $value,
                    $validationContext->getContext(),
                    isset($requiredFields[$fieldName])
                );
            } catch (MigrationValidationException $exception) {
                $this->addValidationExceptionLog($validationContext, $exception, $entityName, $fieldName, (string) $id);
            } catch (\Throwable $exception) {
                $this->addExceptionLog($validationContext, $exception);
            }
        }
    }

    /**
     * Recursively validates nested entities within association fields.
     *
     * @param array<string, mixed>|mixed $value
     */
    private function validateNestedAssociations(
        MigrationValidationContext $validationContext,
        Field $field,
        string $fieldPath,
        mixed $value,
    ): void {
        if (!\is_array($value)) {
            // skip as this is handled by field validation
            return;
        }

        if ($field instanceof TranslationsAssociationField) {
            // skip as this is handled by field validation
            return;
        }

        if ($field instanceof OneToManyAssociationField || $field instanceof ManyToManyAssociationField) {
            $referenceDefinition = $field instanceof ManyToManyAssociationField
                ? $field->getToManyReferenceDefinition()
                : $field->getReferenceDefinition();

            foreach ($value as $nestedEntityData) {
                $this->validateNestedEntityFields(
                    $validationContext,
                    $referenceDefinition,
                    $nestedEntityData,
                    $fieldPath
                );
            }

            return;
        }

        if ($field instanceof ManyToOneAssociationField || $field instanceof OneToOneAssociationField) {
            $this->validateNestedEntityFields(
                $validationContext,
                $field->getReferenceDefinition(),
                $value,
                $fieldPath
            );
        }
    }

    /**
     * Validates a single nested entity's fields and recurses into deeper associations.
     *
     * @param array<string, mixed>|mixed $nestedEntityData
     */
    private function validateNestedEntityFields(
        MigrationValidationContext $validationContext,
        EntityDefinition $referenceDefinition,
        mixed $nestedEntityData,
        string $fieldPath,
    ): void {
        if (!\is_array($nestedEntityData)) {
            return;
        }

        if (\count($nestedEntityData) === 1 && isset($nestedEntityData['id'])) {
            // skip as we only have an ID and no other fields to validate
            return;
        }

        $nestedEntityName = $referenceDefinition->getEntityName();
        $fields = $referenceDefinition->getFields();
        $requiredFields = $this->getRequiredFields($fields, $referenceDefinition);

        $rootEntityName = $validationContext->getEntityDefinition()->getEntityName();
        $rootEntityId = $validationContext->getConvertedData()['id'] ?? null;

        foreach ($requiredFields as $fieldName => $_) {
            $field = $fields->get($fieldName);
            $nestedFieldPath = $fieldPath . '.' . $fieldName;

            if ($field instanceof IdField || $field instanceof AssociationField || $field instanceof FkField) {
                continue;
            }

            if (\array_key_exists($fieldName, $nestedEntityData)) {
                continue;
            }

            $this->addMissingRequiredFieldLog($validationContext, $nestedFieldPath);
        }

        foreach ($nestedEntityData as $fieldName => $value) {
            if ($fieldName === 'id') {
                continue;
            }

            $field = $fields->get($fieldName);
            $nestedFieldPath = $fieldPath . '.' . $fieldName;

            if ($field !== null && $value !== null) {
                $this->validateNestedAssociations($validationContext, $field, $nestedFieldPath, $value);
            }

            try {
                $this->fieldValidationService->validateField(
                    $nestedEntityName,
                    $fieldName,
                    $value,
                    $validationContext->getContext(),
                    isset($requiredFields[$fieldName])
                );
            } catch (MigrationValidationException $exception) {
                $this->addValidationExceptionLog($validationContext, $exception, $rootEntityName, $nestedFieldPath, $rootEntityId);
            } catch (\Throwable $exception) {
                $this->addExceptionLog($validationContext, $exception);
            }
        }
    }

    private function validateId(MigrationValidationContext $validationContext, string $entityName, mixed $id): bool
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
                MigrationValidationException::invalidId((string) $id, $entityName)
            );

            return false;
        }

        return true;
    }

    /**
     * Loads and caches the required fields for the given entity definition.
     * It considers both the definition flags and the database schema.
     *
     * A field is considered required if:
     * - It has the Required flag
     * - It is not a system managed field
     * - Does not have a default value in its entity definition
     * - Its corresponding database column is non-nullable without a default value
     *
     * @return array<string, true>
     */
    private function getRequiredFields(CompiledFieldCollection $fields, EntityDefinition $entityDefinition): array
    {
        $entityName = $entityDefinition->getEntityName();
        if (isset($this->requiredDefinitionFieldsCache[$entityName])) {
            return $this->requiredDefinitionFieldsCache[$entityName];
        }

        $requiredDbColumns = $this->getRequiredDatabaseColumns($entityName);
        $requiredFields = [];

        foreach ($fields->filterByFlag(Required::class) as $field) {
            if (\in_array($field::class, self::SYSTEM_MANAGED_FIELDS, true)) {
                continue;
            }

            if (\in_array($field->getPropertyName(), \array_keys($entityDefinition->getDefaults()), true)) {
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
                ->withSourceData($validationContext->getSourceData())
                ->withConvertedData($convertedData)
                ->withEntityId($entityId)
                ->build(MigrationValidationRequiredFieldMissingLog::class)
        );
    }

    private function addValidationExceptionLog(
        MigrationValidationContext $validationContext,
        MigrationValidationException $exception,
        string $entityName,
        string $fieldName,
        ?string $entityId,
    ): void {
        $logClass = match ($exception->getErrorCode()) {
            MigrationValidationException::VALIDATION_INVALID_ASSOCIATION => MigrationValidationAssociationInvalidLog::class,
            MigrationValidationException::VALIDATION_INVALID_REQUIRED_FIELD_VALUE => MigrationValidationRequiredFieldValueInvalidLog::class,
            MigrationValidationException::VALIDATION_INVALID_OPTIONAL_FIELD_VALUE => MigrationValidationOptionalFieldValueInvalidLog::class,
            MigrationValidationException::VALIDATION_INVALID_TRANSLATION => MigrationValidationRequiredTranslationInvalidLog::class,
            default => MigrationValidationExceptionLog::class,
        };

        $validationContext->getValidationResult()->addLog(
            MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                ->withEntityName($entityName)
                ->withFieldName($fieldName)
                ->withConvertedData($validationContext->getConvertedData())
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
