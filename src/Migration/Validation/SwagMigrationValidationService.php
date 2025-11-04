<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\SwagMigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Validation\Event\SwagMigrationPostValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Event\SwagMigrationPreValidationEvent;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationInvalidFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationInvalidForeignKeyLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationMissingRequiredFieldLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationUnexpectedFieldLog;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function PHPUnit\Framework\isArray;

#[Package('fundamentals@after-sales')]
readonly class SwagMigrationValidationService
{
    public function __construct(
        private DefinitionInstanceRegistry $definitionRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private LoggingServiceInterface $loggingService,
        private MappingServiceInterface $mappingService,
    ) {
    }

    /**
     * @param array<mixed>|null $converted
     */
    public function validate(MigrationContextInterface $migrationContext, Context $shopwareContext, ?array $converted, string $entityName): ?SwagMigrationValidationResult
    {
        if (empty($converted)) {
            return null;
        }

        $entityDefinition = $this->definitionRegistry->getByEntityName($entityName);

        $context = new SwagMigrationValidationContext(
            $shopwareContext,
            $migrationContext,
            $entityDefinition,
            $converted,
        );

        $this->eventDispatcher->dispatch(
            new SwagMigrationPreValidationEvent($context),
        );

        try {
            $this->validateEntityStructure($context);
            $this->validateFields($context);
            $this->validateAssociations($context);
        } catch (\Throwable $e) {
            $logBuilder = SwagMigrationLogBuilder::fromMigrationContext($context->getMigrationContext())
                ->withEntityName($context->getEntityDefinition()->getEntityName())
                ->withConvertedData($context->getConvertedData())
                ->withExceptionMessage($e->getMessage())
                ->withExceptionTrace($e->getTrace());

            if (isset($converted['id']) && Uuid::isValid($converted['id'])) {
                $logBuilder->withEntityId($converted['id']);
            }

            $context->getValidationResult()->addLog(
                $logBuilder->build(ValidationExceptionLog::class)
            );
        }

        $this->eventDispatcher->dispatch(
            new SwagMigrationPostValidationEvent($context),
        );

        foreach ($context->getValidationResult()->getLogs() as $log) {
            $this->loggingService->addLogEntry($log);
        }

        $this->loggingService->saveLogging($context->getContext());

        return $context->getValidationResult();
    }

    private function validateEntityStructure(SwagMigrationValidationContext $context): void
    {
        $fields = $context->getEntityDefinition()->getFields();

        $requiredFields = array_values(array_map(
            static fn (Field $field) => $field->getPropertyName(),
            $fields->filterByFlag(Required::class)->getElements()
        ));

        $convertedFieldNames = array_keys($context->getConvertedData());
        $missingRequiredFields = array_diff($requiredFields, $convertedFieldNames);

        foreach ($missingRequiredFields as $missingField) {
            $context->getValidationResult()->addLog(
                SwagMigrationLogBuilder::fromMigrationContext($context->getMigrationContext())
                    ->withEntityName($context->getEntityDefinition()->getEntityName())
                    ->withFieldName($missingField)
                    ->withConvertedData($context->getConvertedData())
                    ->withEntityId($context->getConvertedData()['id'] ?? null)
                    ->build(ValidationMissingRequiredFieldLog::class)
            );
        }

        $unexpectedFields = array_diff($convertedFieldNames, array_keys($fields->getElements()));

        foreach ($unexpectedFields as $unexpectedField) {
            $context->getValidationResult()->addLog(
                SwagMigrationLogBuilder::fromMigrationContext($context->getMigrationContext())
                    ->withEntityName($context->getEntityDefinition()->getEntityName())
                    ->withFieldName($unexpectedField)
                    ->withConvertedData($context->getConvertedData())
                    ->withEntityId($context->getConvertedData()['id'] ?? null)
                    ->build(ValidationUnexpectedFieldLog::class)
            );
        }
    }

    private function validateFields(SwagMigrationValidationContext $context): void
    {
        $fields = $context->getEntityDefinition()->getFields();

        if (!isset($context->getConvertedData()['id'])) {
            throw MigrationException::unexpectedNullValue('id');
        }

        if (!Uuid::isValid($context->getConvertedData()['id'])) {
            throw MigrationException::invalidId($context->getConvertedData()['id'], $context->getEntityDefinition()->getEntityName());
        }

        $entityExistence = EntityExistence::createForEntity(
            $context->getEntityDefinition()->getEntityName(),
            ['id' => $context->getConvertedData()['id']],
        );

        $parameters = new WriteParameterBag(
            $context->getEntityDefinition(),
            WriteContext::createFromContext($context->getContext()),
            '',
            new WriteCommandQueue(),
        );

        foreach ($context->getConvertedData() as $fieldName => $value) {
            if (!$fields->has($fieldName)) {
                continue;
            }

            $field = clone $fields->get($fieldName);
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
                $context->getValidationResult()->addLog(
                    SwagMigrationLogBuilder::fromMigrationContext($context->getMigrationContext())
                        ->withEntityName($context->getEntityDefinition()->getEntityName())
                        ->withFieldName($fieldName)
                        ->withConvertedData([$fieldName => $value])
                        ->withExceptionMessage($e->getMessage())
                        ->withExceptionTrace($e->getTrace())
                        ->withEntityId($context->getConvertedData()['id'] ?? null)
                        ->build(ValidationInvalidFieldValueLog::class)
                );
            }
        }
    }

    private function validateAssociations(SwagMigrationValidationContext $context): void
    {
        $fields = $context->getEntityDefinition()->getFields();

        $fkFields = array_values(array_map(
            static fn (Field $field) => $field->getPropertyName(),
            $fields->filterInstance(FkField::class)->getElements()
        ));

        foreach ($fkFields as $fkFieldName) {
            if (!isset($context->getConvertedData()[$fkFieldName])) {
                continue;
            }

            $fkValue = $context->getConvertedData()[$fkFieldName];

            if ($fkValue === '') {
                continue;
            }

            $fkField = $fields->get($fkFieldName);

            if (!$fkField instanceof FkField) {
                throw MigrationException::unexpectedNullValue($fkFieldName);
            }

            $referenceEntity = $fkField->getReferenceEntity();

            if (!$referenceEntity) {
                throw MigrationException::unexpectedNullValue($fkFieldName);
            }

            $hasMapping = $this->mappingService->hasValidMappingByEntityUuid(
                $context->getMigrationContext()->getConnection()->getId(),
                $referenceEntity,
                $fkValue,
                $context->getContext()
            );

            if (!$hasMapping) {
                $context->getValidationResult()->addLog(
                    SwagMigrationLogBuilder::fromMigrationContext($context->getMigrationContext())
                        ->withEntityName($context->getEntityDefinition()->getEntityName())
                        ->withFieldName($fkFieldName)
                        ->withConvertedData([$fkFieldName => $fkValue])
                        ->withEntityId($context->getConvertedData()['id'] ?? null)
                        ->build(ValidationInvalidForeignKeyLog::class)
                );
            }
        }
    }
}
