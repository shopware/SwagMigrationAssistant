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
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
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
class MigrationValidationService implements ResetInterface
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
     * @throws \Exception|Exception
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

        $this->validateEntityStructure($validationContext);
        $this->validateFieldValues($validationContext);

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
     * @throws Exception|\Exception
     */
    private function validateEntityStructure(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();
        $entityName = $entityDefinition->getEntityName();

        $convertedData = $validationContext->getConvertedData();
        $fields = $entityDefinition->getFields();

        $requiredFields = $this->getRequiredFields($fields, $entityName);
        $convertedFieldNames = array_keys($convertedData);

        $missingRequiredFields = array_diff(
            array_keys($requiredFields),
            $convertedFieldNames
        );

        foreach ($missingRequiredFields as $missingField) {
            $validationContext->getValidationResult()->addLog(
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
     * @throws \Exception
     */
    private function validateFieldValues(MigrationValidationContext $validationContext): void
    {
        $entityDefinition = $validationContext->getEntityDefinition();
        $fields = $entityDefinition->getFields();

        $convertedData = $validationContext->getConvertedData();
        $id = $convertedData['id'] ?? null;

        if ($id === null) {
            $this->addExceptionLog(
                $validationContext,
                MigrationValidationException::unexpectedNullValue('id')
            );

            return;
        }

        if (!Uuid::isValid($id)) {
            $this->addExceptionLog(
                $validationContext,
                MigrationValidationException::invalidId($id, $entityDefinition->getEntityName())
            );

            return;
        }

        foreach ($convertedData as $fieldName => $value) {
            if (!$fields->has($fieldName)) {
                continue;
            }

            $field = clone $fields->get($fieldName);

            try {
                if ($field instanceof ManyToManyAssociationField || $field instanceof OneToManyAssociationField) {
                    $this->validateToManyAssociationStructure(
                        $validationContext,
                        $fieldName,
                        $value,
                    );

                    continue;
                }

                if ($field instanceof ManyToOneAssociationField || $field instanceof OneToOneAssociationField) {
                    $this->validateToOneAssociationStructure(
                        $validationContext,
                        $fieldName,
                        $value,
                    );

                    continue;
                }

                if ($field instanceof AssociationField) {
                    continue;
                }

                $this->validateFieldByFieldSerializer($validationContext, $field, $value, $id);
            } catch (MigrationValidationException $exception) {
                $this->addValidationExceptionLog($validationContext, $exception, $fieldName, $value, $id);
            } catch (\Throwable $exception) {
                $this->addExceptionLog($validationContext, $exception);
            }
        }
    }

    /**
     * @throws MigrationValidationException|\Exception|Exception
     */
    private function validateFieldByFieldSerializer(
        MigrationValidationContext $validationContext,
        Field $field,
        mixed $value,
        string $id,
    ): void {
        $entityDefinition = $validationContext->getEntityDefinition();
        $fields = $entityDefinition->getFields();
        $entityName = $entityDefinition->getEntityName();

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
        $isRequired = isset($requiredFields[$field->getPropertyName()]);

        /**
         * Replace all flags with Required to force the serializer to validate this field.
         * AbstractFieldSerializer::requiresValidation() skips validation for fields without Required flag.
         * The field is cloned before this method is called to avoid mutating the original definition.
         */
        $field->setFlags(new Required());

        $keyValue = new KeyValuePair(
            $field->getPropertyName(),
            $value,
            true
        );

        try {
            $serializer = $field->getSerializer();

            // Consume the generator to trigger validation. Keys are not needed
            \iterator_to_array($serializer->encode(
                $field,
                $entityExistence,
                $keyValue,
                $parameters
            ), false);
        } catch (\Throwable $e) {
            if ($field instanceof TranslationsAssociationField) {
                throw MigrationValidationException::invalidTranslation($entityName, $field->getPropertyName(), $e->getMessage());
            }

            if ($isRequired) {
                throw MigrationValidationException::invalidRequiredFieldValue($entityName, $field->getPropertyName(), $e->getMessage());
            }

            throw MigrationValidationException::invalidOptionalFieldValue($entityName, $field->getPropertyName(), $e->getMessage());
        }
    }

    /**
     * Validates the structure of a to-many association field value (ManyToMany, OneToMany).
     *
     * Validates:
     * - Association value is an array
     * - Each entry in the association is an array
     * - Each entry's 'id' field (if present) is a valid UUID
     *
     * @throws MigrationValidationException
     */
    private function validateToManyAssociationStructure(
        MigrationValidationContext $validationContext,
        string $fieldName,
        mixed $value,
    ): void {
        $entityName = $validationContext->getEntityDefinition()->getEntityName();

        if (!\is_array($value)) {
            throw MigrationValidationException::invalidAssociation(
                $entityName,
                $fieldName,
                \sprintf('must be an array, got %s', \get_debug_type($value))
            );
        }

        foreach ($value as $index => $entry) {
            if (!\is_array($entry)) {
                throw MigrationValidationException::invalidAssociation(
                    $entityName,
                    $fieldName . '/' . $index,
                    \sprintf('entry at index %s must be an array, got %s', $index, \get_debug_type($entry))
                );
            }

            if (isset($entry['id']) && !Uuid::isValid($entry['id'])) {
                throw MigrationValidationException::invalidAssociation(
                    $entityName,
                    $fieldName . '/' . $index . '/id',
                    \sprintf('invalid UUID "%s" at index %s', $entry['id'], $index)
                );
            }
        }
    }

    /**
     * Validates the structure of a to-one association field value (ManyToOne, OneToOne).
     *
     * Validates:
     * - Association value is an array (object structure)
     * - The 'id' field (if present) is a valid UUID
     *
     * @throws MigrationValidationException
     */
    private function validateToOneAssociationStructure(
        MigrationValidationContext $validationContext,
        string $fieldName,
        mixed $value,
    ): void {
        $entityName = $validationContext->getEntityDefinition()->getEntityName();

        if (!\is_array($value)) {
            throw MigrationValidationException::invalidAssociation(
                $entityName,
                $fieldName,
                \sprintf('must be an array, got %s', \get_debug_type($value))
            );
        }

        if (isset($value['id']) && !Uuid::isValid($value['id'])) {
            throw MigrationValidationException::invalidAssociation(
                $entityName,
                $fieldName . '/id',
                \sprintf('invalid UUID "%s"', $value['id'])
            );
        }
    }

    /**
     * Gets the map of required field property names for the given entity and caches the result for future calls.
     *
     * A field is considered required if:
     * - It has the Required flag in the entity definition, AND
     * - It's either not StorageAware (no direct database column), OR its database column is non-nullable without a default value.
     *
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

        $this->requiredDefinitionFieldsCache[$entityName] = $requiredFields;

        return $requiredFields;
    }

    /**
     * Gets the map of required database columns for the given entity.
     * A required database column is defined as a column that is non-nullable, has no default value, and is not auto-incrementing.
     *
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

    private function addValidationExceptionLog(
        MigrationValidationContext $validationContext,
        MigrationValidationException $exception,
        string $fieldName,
        mixed $value,
        string $entityId,
    ): void {
        $entityDefinition = $validationContext->getEntityDefinition();

        $logClass = match ($exception->getErrorCode()) {
            MigrationValidationException::VALIDATION_INVALID_ASSOCIATION => MigrationValidationInvalidAssociationLog::class,
            MigrationValidationException::VALIDATION_INVALID_REQUIRED_FIELD_VALUE => MigrationValidationInvalidRequiredFieldValueLog::class,
            MigrationValidationException::VALIDATION_INVALID_OPTIONAL_FIELD_VALUE => MigrationValidationInvalidOptionalFieldValueLog::class,
            MigrationValidationException::VALIDATION_INVALID_TRANSLATION => MigrationValidationInvalidRequiredTranslation::class,
            default => MigrationValidationExceptionLog::class,
        };

        $validationContext->getValidationResult()->addLog(
            MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                ->withEntityName($entityDefinition->getEntityName())
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
        $entityDefinition = $validationContext->getEntityDefinition();
        $convertedData = $validationContext->getConvertedData();

        $validationContext->getValidationResult()->addLog(
            MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                ->withEntityName($entityDefinition->getEntityName())
                ->withSourceData($validationContext->getSourceData())
                ->withConvertedData($convertedData)
                ->withExceptionMessage($exception->getMessage())
                ->withExceptionTrace($exception->getTrace())
                ->withEntityId($convertedData['id'] ?? null)
                ->build(MigrationValidationExceptionLog::class)
        );
    }
}
