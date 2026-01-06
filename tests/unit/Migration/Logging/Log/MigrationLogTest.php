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
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\Log\CannotConvertChildEntityLog;
use SwagMigrationAssistant\Migration\Logging\Log\CannotConvertEntityLog;
use SwagMigrationAssistant\Migration\Logging\Log\CannotGetFileRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\CannotReadEntityCountLog;
use SwagMigrationAssistant\Migration\Logging\Log\DataSetNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\Log\DocumentTypeNotSupportedLog;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\EntityAlreadyExistsRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\FieldReassignedRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\InvalidUnserializedDataLog;
use SwagMigrationAssistant\Migration\Logging\Log\MessageQueueExceptionLog;
use SwagMigrationAssistant\Migration\Logging\Log\MimeTypeErrorLog;
use SwagMigrationAssistant\Migration\Logging\Log\ProcessorNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\Log\RunAbortedAutomaticallyLog;
use SwagMigrationAssistant\Migration\Logging\Log\TemporaryFileErrorLog;
use SwagMigrationAssistant\Migration\Logging\Log\ThemeCompilingErrorRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\UnknownEntityLog;
use SwagMigrationAssistant\Migration\Logging\Log\UnsupportedObjectTypeLog;
use SwagMigrationAssistant\Migration\Logging\Log\WriteExceptionRunLog;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidRequiredFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationMissingRequiredFieldLog;
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

        yield MigrationValidationInvalidRequiredFieldValueLog::class => [
            'logClass' => MigrationValidationInvalidRequiredFieldValueLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_INVALID_FIELD_VALUE',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => true,
        ];

        yield MigrationValidationMissingRequiredFieldLog::class => [
            'logClass' => MigrationValidationMissingRequiredFieldLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_MISSING_REQUIRED_FIELD',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield AssociationRequiredMissingLog::class => [
            'logClass' => AssociationRequiredMissingLog::class,
            'code' => 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield CannotConvertChildEntityLog::class => [
            'logClass' => CannotConvertChildEntityLog::class,
            'code' => 'SWAG_MIGRATION_CANNOT_CONVERT_CHILD_ENTITY',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield CannotConvertEntityLog::class => [
            'logClass' => CannotConvertEntityLog::class,
            'code' => 'SWAG_MIGRATION_CANNOT_CONVERT',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield CannotGetFileRunLog::class => [
            'logClass' => CannotGetFileRunLog::class,
            'code' => 'SWAG_MIGRATION_CANNOT_GET_FILE',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield CannotReadEntityCountLog::class => [
            'logClass' => CannotReadEntityCountLog::class,
            'code' => 'SWAG_MIGRATION__COULD_NOT_READ_ENTITY_COUNT',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield DataSetNotFoundLog::class => [
            'logClass' => DataSetNotFoundLog::class,
            'code' => 'SWAG_MIGRATION__DATASET_NOT_FOUND',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield DocumentTypeNotSupportedLog::class => [
            'logClass' => DocumentTypeNotSupportedLog::class,
            'code' => 'SWAG_MIGRATION__DOCUMENT_TYPE_NOT_SUPPORTED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield EmptyNecessaryFieldRunLog::class => [
            'logClass' => EmptyNecessaryFieldRunLog::class,
            'code' => 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield EntityAlreadyExistsRunLog::class => [
            'logClass' => EntityAlreadyExistsRunLog::class,
            'code' => 'SWAG_MIGRATION_ENTITY_ALREADY_EXISTS',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_INFO,
            'userFixable' => false,
        ];

        yield ExceptionRunLog::class => [
            'logClass' => ExceptionRunLog::class,
            'code' => 'SWAG_MIGRATION_RUN_EXCEPTION',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield FieldReassignedRunLog::class => [
            'logClass' => FieldReassignedRunLog::class,
            'code' => 'SWAG_MIGRATION_ENTITY_FIELD_REASSIGNED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_INFO,
            'userFixable' => false,
        ];

        yield InvalidUnserializedDataLog::class => [
            'logClass' => InvalidUnserializedDataLog::class,
            'code' => 'SWAG_MIGRATION__SHOPWARE_INVALID_UNSERIALIZED_DATA',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield MessageQueueExceptionLog::class => [
            'logClass' => MessageQueueExceptionLog::class,
            'code' => 'SWAG_MIGRATION_MESSAGE_QUEUE_EXCEPTION',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_INFO,
            'userFixable' => false,
        ];

        yield MimeTypeErrorLog::class => [
            'logClass' => MimeTypeErrorLog::class,
            'code' => 'SWAG_MIGRATION__MIME_TYPE_COULD_NOT_BE_DETERMINED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield ProcessorNotFoundLog::class => [
            'logClass' => ProcessorNotFoundLog::class,
            'code' => 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield RunAbortedAutomaticallyLog::class => [
            'logClass' => RunAbortedAutomaticallyLog::class,
            'code' => 'SWAG_MIGRATION_RUN_ABORTED_AUTOMATICALLY_EXCEPTION',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield TemporaryFileErrorLog::class => [
            'logClass' => TemporaryFileErrorLog::class,
            'code' => 'SWAG_MIGRATION__TEMPORARY_FILE_COULD_NOT_BE_CREATED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield ThemeCompilingErrorRunLog::class => [
            'logClass' => ThemeCompilingErrorRunLog::class,
            'code' => 'SWAG_MIGRATION__THEME_COMPILING_ERROR',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield UnknownEntityLog::class => [
            'logClass' => UnknownEntityLog::class,
            'code' => 'SWAG_MIGRATION_ENTITY_UNKNOWN',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield UnsupportedObjectTypeLog::class => [
            'logClass' => UnsupportedObjectTypeLog::class,
            'code' => 'SWAG_MIGRATION__SHOPWARE_UNSUPPORTED_OBJECT_TYPE',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => false,
        ];

        yield WriteExceptionRunLog::class => [
            'logClass' => WriteExceptionRunLog::class,
            'code' => 'SWAG_MIGRATION__WRITE_EXCEPTION_OCCURRED',
            'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];
    }
}
