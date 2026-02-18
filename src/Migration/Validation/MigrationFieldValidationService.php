<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MigrationFieldValidationService implements ResetInterface
{
    private ?WriteContext $writeContext = null;

    /**
     * @var array<string, array{EntityDefinition, Field}> keyed by entityName + fieldPath
     */
    private array $definitionFieldCache = [];

    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
    ) {
    }

    /**
     * Validates a single field value against its entity definition.
     * Supports nested field paths like "prices.shippingMethodId".
     * Silently skips validation for unknown entities & fields.
     *
     * @throws MigrationValidationException
     */
    public function validateField(
        string $entityName,
        string $fieldName,
        mixed $value,
        Context $context,
        bool $isRequired = true,
    ): void {
        if (!$this->definitionRegistry->has($entityName)) {
            // silently skip validation for unknown entities as the entity is not available in
            // the target system and this is not a validation failure
            return;
        }

        $resolved = $this->resolveFieldPath($entityName, $fieldName);

        if ($resolved === null) {
            // silently skip validation for unknown fields as the field is not available in
            // the target system and this is not a validation failure
            return;
        }

        [$entityDefinition, $field] = $resolved;

        if ($field instanceof AssociationField) {
            $this->validateAssociationStructure($field, $value, $entityDefinition->getEntityName());

            return;
        }

        $this->validateScalarField($field, $value, $isRequired, $entityDefinition, $context);
    }

    /**
     * Resolves a potentially nested field path (e.g., "prices.shippingMethodId") to its target.
     * Traverses through association fields to find the final entity definition and field.
     *
     * @return array{EntityDefinition, Field}|null
     */
    public function resolveFieldPath(string $entityName, string $fieldPath): ?array
    {
        if (!$this->definitionRegistry->has($entityName)) {
            return null;
        }

        $currentDefinition = $this->definitionRegistry->getByEntityName($entityName);

        if (isset($this->definitionFieldCache[$entityName . $fieldPath])) {
            return $this->definitionFieldCache[$entityName . $fieldPath];
        }

        $paths = \explode('.', $fieldPath);

        foreach ($paths as $index => $path) {
            $fields = $currentDefinition->getFields();

            if (!$fields->has($path)) {
                return null;
            }

            $field = $fields->get($path);

            if ($index === \count($paths) - 1) {
                // needed to avoid side effects when modifying flags
                $field = clone $field;

                /**
                 * Replace all flags with Required to force the serializer to validate this field.
                 * AbstractFieldSerializer::requiresValidation() skips validation for fields without Required flag.
                 * The field is cloned before this method is called to avoid mutating the original definition.
                 */
                $field->setFlags(new Required());

                $this->definitionFieldCache[$entityName . $fieldPath] = [$currentDefinition, $field];

                return [$currentDefinition, $field];
            }

            if (!$field instanceof AssociationField) {
                return null;
            }

            $referenceEntity = $field instanceof ManyToManyAssociationField
                ? $field->getToManyReferenceDefinition()->getEntityName()
                : $field->getReferenceDefinition()->getEntityName();

            if (!$this->definitionRegistry->has($referenceEntity)) {
                return null;
            }

            $currentDefinition = $this->definitionRegistry->getByEntityName($referenceEntity);
        }

        return null;
    }

    public function reset(): void
    {
        $this->writeContext = null;
        $this->definitionFieldCache = [];
    }

    /**
     * Validates the structure of an association field value (not its nested content).
     */
    private function validateAssociationStructure(AssociationField $field, mixed $value, string $entityName): void
    {
        if ($field instanceof TranslationsAssociationField) {
            $this->validateTranslationAssociationStructure($field, $value, $entityName);

            return;
        }

        if ($field instanceof ManyToManyAssociationField || $field instanceof OneToManyAssociationField) {
            $this->validateToManyAssociationStructure($field, $value, $entityName);

            return;
        }

        if ($field instanceof ManyToOneAssociationField || $field instanceof OneToOneAssociationField) {
            $this->validateToOneAssociationStructure($field, $value, $entityName);
        }
    }

    /**
     * Validates a scalar (non-association) field using its serializer.
     */
    private function validateScalarField(
        Field $field,
        mixed $value,
        bool $isRequired,
        EntityDefinition $entityDefinition,
        Context $context,
    ): void {
        if (!$isRequired && $value === null) {
            // skip validation for optional fields with null value as the serializer could
            // throw an error for missing required fields
            return;
        }

        $existence = EntityExistence::createForEntity(
            $entityDefinition->getEntityName(),
            ['id' => '019c4cb1f42c71faaf555445277a6250'], // random uuid does not need to be unique
        );

        $parameters = new WriteParameterBag(
            $entityDefinition,
            $this->getWriteContext($context),
            '',
            new WriteCommandQueue(),
        );

        $this->validateFieldByFieldSerializer($field, $value, $isRequired, $existence, $parameters);
    }

    /**
     * @throws MigrationValidationException
     */
    private function validateFieldByFieldSerializer(
        Field $field,
        mixed $value,
        bool $isRequired,
        EntityExistence $existence,
        WriteParameterBag $parameters,
    ): void {
        $keyValue = new KeyValuePair(
            $field->getPropertyName(),
            $value,
            true
        );

        $serializer = $field->getSerializer();

        try {
            $iterator = $serializer->encode(
                $field,
                $existence,
                $keyValue,
                $parameters
            );

            foreach ($iterator as $_) {
                // consume the generator to trigger validation. serialization results are not needed
            }
        } catch (\Throwable $e) {
            $entityName = $parameters->getDefinition()->getEntityName();
            $propertyName = $field->getPropertyName();

            if ($isRequired) {
                throw MigrationValidationException::invalidRequiredFieldValue(
                    $entityName,
                    $propertyName,
                    $e
                );
            }

            throw MigrationValidationException::invalidOptionalFieldValue(
                $entityName,
                $propertyName,
                $e
            );
        }
    }

    /**
     * @throws MigrationValidationException
     */
    private function validateToManyAssociationStructure(Field $field, mixed $value, string $entityName): void
    {
        if (!\is_array($value)) {
            throw MigrationValidationException::invalidAssociation(
                $entityName,
                $field->getPropertyName(),
                \sprintf('must be an array, got %s', \get_debug_type($value))
            );
        }

        foreach ($value as $index => $entry) {
            if (!\is_array($entry)) {
                throw MigrationValidationException::invalidAssociation(
                    $entityName,
                    $field->getPropertyName(),
                    \sprintf('entry at index %s must be an array, got %s', $index, \get_debug_type($entry))
                );
            }

            if (isset($entry['id']) && !Uuid::isValid($entry['id'])) {
                throw MigrationValidationException::invalidAssociation(
                    $entityName,
                    $field->getPropertyName() . '.id',
                    \sprintf('invalid UUID "%s" at index %s', $entry['id'], $index)
                );
            }
        }
    }

    /**
     * @throws MigrationValidationException
     */
    private function validateToOneAssociationStructure(Field $field, mixed $value, string $entityName): void
    {
        if (!\is_array($value)) {
            throw MigrationValidationException::invalidAssociation(
                $entityName,
                $field->getPropertyName(),
                \sprintf('must be an array, got %s', \get_debug_type($value))
            );
        }

        if (isset($value['id']) && !Uuid::isValid($value['id'])) {
            throw MigrationValidationException::invalidAssociation(
                $entityName,
                $field->getPropertyName() . '.id',
                \sprintf('invalid UUID "%s"', $value['id'])
            );
        }
    }

    /**
     * @throws MigrationValidationException
     */
    private function validateTranslationAssociationStructure(TranslationsAssociationField $field, mixed $value, string $entityName): void
    {
        if (!\is_array($value)) {
            throw MigrationValidationException::invalidTranslation(
                $entityName,
                $field->getPropertyName(),
            );
        }

        foreach ($value as $key => $translation) {
            if (!\is_array($translation)) {
                throw MigrationValidationException::invalidTranslation(
                    $entityName,
                    $field->getPropertyName() . '.' . $key,
                );
            }
        }
    }

    private function getWriteContext(Context $context): WriteContext
    {
        if ($this->writeContext === null) {
            $this->writeContext = WriteContext::createFromContext($context);
        }

        return $this->writeContext;
    }
}
