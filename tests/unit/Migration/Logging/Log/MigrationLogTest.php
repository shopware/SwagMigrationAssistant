<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\Logging\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertAssociationMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertChildEntityFailedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertDocumentTypeUnsupportedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertEntityAlreadyExistsLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertEntityUnknownLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertFieldReassignedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertObjectTypeUnsupportedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertSourceDataIncompleteLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertUnserializedDataInvalidLog;
use SwagMigrationAssistant\Migration\Logging\Log\FetchDataSetMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\FetchEntityCountFailedLog;
use SwagMigrationAssistant\Migration\Logging\Log\FetchProcessorMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\MediaFileMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\MediaMimeTypeUnknownLog;
use SwagMigrationAssistant\Migration\Logging\Log\MediaTemporaryFileFailedLog;
use SwagMigrationAssistant\Migration\Logging\Log\RunAbortedLog;
use SwagMigrationAssistant\Migration\Logging\Log\RunExceptionLog;
use SwagMigrationAssistant\Migration\Logging\Log\RunMessageQueueExceptionLog;
use SwagMigrationAssistant\Migration\Logging\Log\WriteExceptionLog;
use SwagMigrationAssistant\Migration\Logging\Log\WriteThemeCompilingFailedLog;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidForeignKeyLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationMissingRequiredFieldLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationUnexpectedFieldLog;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationLogEntry::class)]
class MigrationLogTest extends TestCase
{
    /**
     * @param class-string<AbstractMigrationLogEntry> $logClass
     */
    #[DataProvider('logProvider')]
    public function testLogEntry(string $logClass, string $code, string $level, bool $userFixable): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $connection->setGatewayName(DummyLocalGateway::GATEWAY_NAME);

        $context = new MigrationContext(
            $connection,
            new Shopware54Profile(),
            new DummyLocalGateway(),
            null,
            Uuid::randomHex(),
        );

        $entityId = Uuid::randomHex();

        $logEntry = MigrationLogBuilder::fromMigrationContext($context)
            ->withEntityName('test1')
            ->withFieldName('test2')
            ->withFieldSourcePath('test3')
            ->withSourceData(['test' => 'test4'])
            ->withConvertedData(['test' => 'test5'])
            ->withExceptionMessage('test7')
            ->withExceptionTrace(['test' => 'test8'])
            ->withEntityId($entityId)
            ->build($logClass);

        static::assertInstanceOf($logClass, $logEntry);
        static::assertSame($userFixable, $logEntry->isUserFixable());
        static::assertSame($level, $logEntry->getLevel());
        static::assertSame($code, $logEntry->getCode());
        static::assertSame('test1', $logEntry->getEntityName());
        static::assertSame('test2', $logEntry->getFieldName());
        static::assertSame('test3', $logEntry->getFieldSourcePath());
        static::assertSame(['test' => 'test4'], $logEntry->getSourceData());
        static::assertSame(['test' => 'test5'], $logEntry->getConvertedData());
        static::assertSame('test7', $logEntry->getExceptionMessage());
        static::assertSame(['test' => 'test8'], $logEntry->getExceptionTrace());
        static::assertSame($entityId, $logEntry->getEntityId());
    }

    public static function logProvider(): \Generator
    {
        yield MigrationValidationExceptionLog::class => [
            'logClass' => MigrationValidationExceptionLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_EXCEPTION',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield MigrationValidationInvalidFieldValueLog::class => [
            'logClass' => MigrationValidationInvalidFieldValueLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_INVALID_FIELD_VALUE',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => true,
        ];

        yield MigrationValidationInvalidForeignKeyLog::class => [
            'logClass' => MigrationValidationInvalidForeignKeyLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_INVALID_FOREIGN_KEY',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => true,
        ];

        yield MigrationValidationMissingRequiredFieldLog::class => [
            'logClass' => MigrationValidationMissingRequiredFieldLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_MISSING_REQUIRED_FIELD',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield MigrationValidationUnexpectedFieldLog::class => [
            'logClass' => MigrationValidationUnexpectedFieldLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_UNEXPECTED_FIELD',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => true,
        ];

        yield ConvertAssociationMissingLog::class => [
            'logClass' => ConvertAssociationMissingLog::class,
            'code' => 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield ConvertChildEntityFailedLog::class => [
            'logClass' => ConvertChildEntityFailedLog::class,
            'code' => 'SWAG_MIGRATION_CANNOT_CONVERT_CHILD_ENTITY',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield MediaFileMissingLog::class => [
            'logClass' => MediaFileMissingLog::class,
            'code' => 'SWAG_MIGRATION_CANNOT_GET_FILE',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield FetchEntityCountFailedLog::class => [
            'logClass' => FetchEntityCountFailedLog::class,
            'code' => 'SWAG_MIGRATION__COULD_NOT_READ_ENTITY_COUNT',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield FetchDataSetMissingLog::class => [
            'logClass' => FetchDataSetMissingLog::class,
            'code' => 'SWAG_MIGRATION__DATASET_NOT_FOUND',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield ConvertDocumentTypeUnsupportedLog::class => [
            'logClass' => ConvertDocumentTypeUnsupportedLog::class,
            'code' => 'SWAG_MIGRATION__DOCUMENT_TYPE_NOT_SUPPORTED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield ConvertSourceDataIncompleteLog::class => [
            'logClass' => ConvertSourceDataIncompleteLog::class,
            'code' => 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield ConvertEntityAlreadyExistsLog::class => [
            'logClass' => ConvertEntityAlreadyExistsLog::class,
            'code' => 'SWAG_MIGRATION_ENTITY_ALREADY_EXISTS',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_INFO,
            'userFixable' => false,
        ];

        yield RunExceptionLog::class => [
            'logClass' => RunExceptionLog::class,
            'code' => 'SWAG_MIGRATION_RUN_EXCEPTION',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield ConvertFieldReassignedLog::class => [
            'logClass' => ConvertFieldReassignedLog::class,
            'code' => 'SWAG_MIGRATION_ENTITY_FIELD_REASSIGNED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_INFO,
            'userFixable' => false,
        ];

        yield ConvertUnserializedDataInvalidLog::class => [
            'logClass' => ConvertUnserializedDataInvalidLog::class,
            'code' => 'SWAG_MIGRATION__SHOPWARE_INVALID_UNSERIALIZED_DATA',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield RunMessageQueueExceptionLog::class => [
            'logClass' => RunMessageQueueExceptionLog::class,
            'code' => 'SWAG_MIGRATION_MESSAGE_QUEUE_EXCEPTION',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_INFO,
            'userFixable' => false,
        ];

        yield MediaMimeTypeUnknownLog::class => [
            'logClass' => MediaMimeTypeUnknownLog::class,
            'code' => 'SWAG_MIGRATION__MIME_TYPE_COULD_NOT_BE_DETERMINED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield FetchProcessorMissingLog::class => [
            'logClass' => FetchProcessorMissingLog::class,
            'code' => 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield RunAbortedLog::class => [
            'logClass' => RunAbortedLog::class,
            'code' => 'SWAG_MIGRATION_RUN_ABORTED_AUTOMATICALLY_EXCEPTION',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield MediaTemporaryFileFailedLog::class => [
            'logClass' => MediaTemporaryFileFailedLog::class,
            'code' => 'SWAG_MIGRATION__TEMPORARY_FILE_COULD_NOT_BE_CREATED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield WriteThemeCompilingFailedLog::class => [
            'logClass' => WriteThemeCompilingFailedLog::class,
            'code' => 'SWAG_MIGRATION__THEME_COMPILING_ERROR',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield ConvertEntityUnknownLog::class => [
            'logClass' => ConvertEntityUnknownLog::class,
            'code' => 'SWAG_MIGRATION_ENTITY_UNKNOWN',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield ConvertObjectTypeUnsupportedLog::class => [
            'logClass' => ConvertObjectTypeUnsupportedLog::class,
            'code' => 'SWAG_MIGRATION__SHOPWARE_UNSUPPORTED_OBJECT_TYPE',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield WriteExceptionLog::class => [
            'logClass' => WriteExceptionLog::class,
            'code' => 'SWAG_MIGRATION__WRITE_EXCEPTION_OCCURRED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];
    }
}
