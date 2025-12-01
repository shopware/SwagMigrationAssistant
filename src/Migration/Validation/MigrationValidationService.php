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

#[Package('fundamentals@after-sales')]
readonly class MigrationValidationService
{
    public function __construct(
        private DefinitionInstanceRegistry $definitionRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private LoggingServiceInterface $loggingService,
        private MappingServiceInterface $mappingService,
    ) {
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
                    ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
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

    private function validateEntityStructure(MigrationValidationContext $validationContext): void
    {
        $fields = $validationContext->getEntityDefinition()->getFields();

        $requiredFields = array_values(array_map(
            static fn (Field $field) => $field->getPropertyName(),
            $fields->filterByFlag(Required::class)->getElements()
        ));

        $convertedFieldNames = array_keys($validationContext->getConvertedData());
        $missingRequiredFields = array_diff($requiredFields, $convertedFieldNames);

        foreach ($missingRequiredFields as $missingField) {
            $validationContext->getValidationResult()->addLog(
                MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                    ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
                    ->withFieldName($missingField)
                    ->withConvertedData($validationContext->getConvertedData())
                    ->withEntityId($validationContext->getConvertedData()['id'] ?? null)
                    ->build(MigrationValidationMissingRequiredFieldLog::class)
            );
        }

        $unexpectedFields = array_diff($convertedFieldNames, array_keys($fields->getElements()));

        foreach ($unexpectedFields as $unexpectedField) {
            $validationContext->getValidationResult()->addLog(
                MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                    ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
                    ->withFieldName($unexpectedField)
                    ->withConvertedData($validationContext->getConvertedData())
                    ->withEntityId($validationContext->getConvertedData()['id'] ?? null)
                    ->build(MigrationValidationUnexpectedFieldLog::class)
            );
        }
    }

    private function validateFields(MigrationValidationContext $validationContext): void
    {
        $fields = $validationContext->getEntityDefinition()->getFields();

        if (!isset($validationContext->getConvertedData()['id'])) {
            throw MigrationException::unexpectedNullValue('id');
        }

        if (!Uuid::isValid($validationContext->getConvertedData()['id'])) {
            throw MigrationException::invalidId($validationContext->getConvertedData()['id'], $validationContext->getEntityDefinition()->getEntityName());
        }

        $entityExistence = EntityExistence::createForEntity(
            $validationContext->getEntityDefinition()->getEntityName(),
            ['id' => $validationContext->getConvertedData()['id']],
        );

        $parameters = new WriteParameterBag(
            $validationContext->getEntityDefinition(),
            WriteContext::createFromContext($validationContext->getContext()),
            '',
            new WriteCommandQueue(),
        );

        foreach ($validationContext->getConvertedData() as $fieldName => $value) {
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
                $validationContext->getValidationResult()->addLog(
                    MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                        ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
                        ->withFieldName($fieldName)
                        ->withConvertedData([$fieldName => $value])
                        ->withSourceData($validationContext->getSourceData())
                        ->withExceptionMessage($e->getMessage())
                        ->withExceptionTrace($e->getTrace())
                        ->withEntityId($validationContext->getConvertedData()['id'] ?? null)
                        ->build(MigrationValidationInvalidFieldValueLog::class)
                );
            }
        }
    }

    private function validateAssociations(MigrationValidationContext $validationContext): void
    {
        $fields = $validationContext->getEntityDefinition()->getFields();

        $fkFields = array_values(array_map(
            static fn (Field $field) => $field->getPropertyName(),
            $fields->filterInstance(FkField::class)->getElements()
        ));

        foreach ($fkFields as $fkFieldName) {
            if (!isset($validationContext->getConvertedData()[$fkFieldName])) {
                continue;
            }

            $fkValue = $validationContext->getConvertedData()[$fkFieldName];

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

            $hasMapping = $this->mappingService->hasValidMappingByEntityId(
                $validationContext->getMigrationContext()->getConnection()->getId(),
                $referenceEntity,
                $fkValue,
                $validationContext->getContext()
            );

            if (!$hasMapping) {
                $validationContext->getValidationResult()->addLog(
                    MigrationLogBuilder::fromMigrationContext($validationContext->getMigrationContext())
                        ->withEntityName($validationContext->getEntityDefinition()->getEntityName())
                        ->withFieldName($fkFieldName)
                        ->withConvertedData([$fkFieldName => $fkValue])
                        ->withSourceData($validationContext->getSourceData())
                        ->withEntityId($validationContext->getConvertedData()['id'] ?? null)
                        ->build(MigrationValidationInvalidForeignKeyLog::class)
                );
            }
        }
    }
}
