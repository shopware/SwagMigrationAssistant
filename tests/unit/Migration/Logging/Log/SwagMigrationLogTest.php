<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\Validation\Log;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractSwagMigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\SwagMigrationLogBuilder;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationInvalidFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationInvalidForeignKeyLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationMissingRequiredFieldLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationUnexpectedFieldLog;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class SwagMigrationLogTest extends TestCase
{
    /**
     * @param class-string<AbstractSwagMigrationLogEntry> $logClass
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

        $logEntry = SwagMigrationLogBuilder::fromMigrationContext($context)
            ->withEntityName('test1')
            ->withFieldName('test2')
            ->withFieldSourcePath('test3')
            ->withSourceData(['test' => 'test4'])
            ->withConvertedData(['test' => 'test5'])
            ->withUsedMapping(['test' => 'test6'])
            ->withExceptionMessage('test7')
            ->withExceptionTrace(['test' => 'test8'])
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
        static::assertSame(['test' => 'test6'], $logEntry->getUsedMapping());
        static::assertSame('test7', $logEntry->getExceptionMessage());
        static::assertSame(['test' => 'test8'], $logEntry->getExceptionTrace());
    }

    public static function logProvider(): \Generator
    {
        yield ValidationExceptionLog::class => [
            'logClass' => ValidationExceptionLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_EXCEPTION',
            'level' => AbstractSwagMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => false,
        ];

        yield ValidationInvalidFieldValueLog::class => [
            'logClass' => ValidationInvalidFieldValueLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_INVALID_FIELD_VALUE',
            'level' => AbstractSwagMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => true,
        ];

        yield ValidationInvalidForeignKeyLog::class => [
            'logClass' => ValidationInvalidForeignKeyLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_INVALID_FOREIGN_KEY',
            'level' => AbstractSwagMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => true,
        ];

        yield ValidationMissingRequiredFieldLog::class => [
            'logClass' => ValidationMissingRequiredFieldLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_MISSING_REQUIRED_FIELD',
            'level' => AbstractSwagMigrationLogEntry::LOG_LEVEL_ERROR,
            'userFixable' => true,
        ];

        yield ValidationUnexpectedFieldLog::class => [
            'logClass' => ValidationUnexpectedFieldLog::class,
            'code' => 'SWAG_MIGRATION_VALIDATION_UNEXPECTED_FIELD',
            'level' => AbstractSwagMigrationLogEntry::LOG_LEVEL_WARNING,
            'userFixable' => true,
        ];
    }
}
