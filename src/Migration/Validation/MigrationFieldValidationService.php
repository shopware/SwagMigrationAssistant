<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use SwagMigrationAssistant\Exception\MigrationException;

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
     * Validates a field value using the DAL field serializer.
     *
     * @throws WriteConstraintViolationException|MigrationException|\Exception if the value is not valid
     */
    public function validateFieldValue(
        string $entityName,
        string $fieldName,
        mixed $value,
        Context $context,
        ?string $entityId = null,
    ): void {
        $entityDefinition = $this->definitionRegistry->getByEntityName($entityName);
        $fields = $entityDefinition->getFields();

        if (!$fields->has($fieldName)) {
            throw MigrationException::entityFieldNotFound($entityName, $fieldName);
        }

        $field = $fields->get($fieldName);

        $entityExistence = EntityExistence::createForEntity(
            $entityDefinition->getEntityName(),
            ['id' => $entityId ?? Uuid::randomHex()],
        );

        $parameters = new WriteParameterBag(
            $entityDefinition,
            WriteContext::createFromContext($context),
            '',
            new WriteCommandQueue(),
        );

        $field = clone $field;
        $field->setFlags(new Required());

        $keyValue = new KeyValuePair(
            $field->getPropertyName(),
            $value,
            true,
        );

        $serializer = $field->getSerializer();
        \iterator_to_array($serializer->encode($field, $entityExistence, $keyValue, $parameters), false);
    }
}
