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

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
readonly class MigrationFieldValidationService
{
    public function __construct(
        private DefinitionInstanceRegistry $definitionRegistry,
    ) {
    }

    /**
     * Validates a single field value against its entity definition.
     * Silently skips validation for unknown entities or fields.
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
            return;
        }

        $entityDefinition = $this->definitionRegistry->getByEntityName($entityName);
        $fields = $entityDefinition->getFields();

        if (!$fields->has($fieldName)) {
            throw MigrationValidationException::entityFieldNotFound($entityName, $fieldName);
        }

        $field = clone $fields->get($fieldName);

        if ($field instanceof AssociationField) {
            $this->validateAssociationStructure($field, $value, $entityName);

            return;
        }

        $this->validateScalarField($field, $value, $isRequired, $entityDefinition, $context);
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
        $existence = EntityExistence::createForEntity(
            $entityDefinition->getEntityName(),
            ['id' => Uuid::randomHex()],
        );

        $parameters = new WriteParameterBag(
            $entityDefinition,
            WriteContext::createFromContext($context),
            '',
            new WriteCommandQueue(),
        );

        $this->validateFieldByFieldSerializer($field, $value, $isRequired, $existence, $parameters);
    }

    private function validateFieldByFieldSerializer(
        Field $field,
        mixed $value,
        bool $isRequired,
        EntityExistence $existence,
        WriteParameterBag $parameters,
    ): void {
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

        $serializer = $field->getSerializer();

        try {
            // Consume the generator to trigger validation. Keys are not needed
            \iterator_to_array($serializer->encode(
                $field,
                $existence,
                $keyValue,
                $parameters
            ), false);
        } catch (\Throwable $e) {
            $entityName = $parameters->getDefinition()->getEntityName();
            $propertyName = $field->getPropertyName();

            if ($field instanceof TranslationsAssociationField) {
                throw MigrationValidationException::invalidTranslation(
                    $entityName,
                    $propertyName,
                    $e,
                );
            }

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
}
