<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\ErrorResolution\Entity\SwagMigrationFixDefinition;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\TruncateMigrationHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Message\TruncateMigrationMessage;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('fundamentals@after-sales')]
#[CoversClass(TruncateMigrationHandler::class)]
class TruncateMigrationHandlerTest extends TestCase
{
    private MockObject&Connection $connection;

    private MockObject&MessageBusInterface $messageBus;

    private TruncateMigrationHandler $handler;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->handler = new TruncateMigrationHandler(
            $this->connection,
            $this->messageBus,
            new MigrationConfiguration(),
        );
    }

    public function testInvokeWithNullTableNameStartsFromFirstTable(): void
    {
        $message = new TruncateMigrationMessage(null);

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_mapping LIMIT 100')
            ->willReturn(50);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_logging';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFirstTableAndFullBatchRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_mapping');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_mapping LIMIT 100')
            ->willReturn(100);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_mapping';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFirstTableAndPartialBatchDispatchesNextTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_mapping');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_mapping LIMIT 100')
            ->willReturn(50);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_logging';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithMiddleTableAndFullBatchRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_data');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_data LIMIT 100')
            ->willReturn(100);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_data';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithMiddleTableAndPartialBatchDispatchesNextTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_data');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_data LIMIT 100')
            ->willReturn(50);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_media_file';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithLastTableAndFullBatchRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_connection');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_connection LIMIT 100')
            ->willReturn(100);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_connection';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithLastTableAndPartialBatchUpdatesGeneralSetting(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_connection');

        $this->connection
            ->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(static function ($sql) {
                if (str_contains($sql, 'DELETE FROM swag_migration_connection')) {
                    return 50;
                }
                if (str_contains($sql, 'UPDATE swag_migration_general_setting')) {
                    static::assertStringContainsString('`is_reset` = 0', $sql);

                    return 1;
                }

                static::fail('Unexpected SQL statement: ' . $sql);
            });

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithZeroAffectedRowsMovesToNextTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_logging');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_logging LIMIT 100')
            ->willReturn(0);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_data';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeProcessesAllTablesInCorrectOrder(): void
    {
        $expectedTables = [
            SwagMigrationMappingDefinition::ENTITY_NAME,
            SwagMigrationLoggingDefinition::ENTITY_NAME,
            SwagMigrationDataDefinition::ENTITY_NAME,
            SwagMigrationMediaFileDefinition::ENTITY_NAME,
            SwagMigrationFixDefinition::ENTITY_NAME,
            SwagMigrationRunDefinition::ENTITY_NAME,
            SwagMigrationConnectionDefinition::ENTITY_NAME,
        ];

        foreach ($expectedTables as $index => $tableName) {
            $message = new TruncateMigrationMessage($tableName);

            $connection = $this->createMock(Connection::class);
            $messageBus = $this->createMock(MessageBusInterface::class);
            $handler = new TruncateMigrationHandler(
                $connection,
                $messageBus,
                new MigrationConfiguration()
            );

            if ($index < \count($expectedTables) - 1) {
                $connection
                    ->expects($this->once())
                    ->method('executeStatement')
                    ->with('DELETE FROM ' . $tableName . ' LIMIT 100')
                    ->willReturn(10);

                $nextTable = $expectedTables[$index + 1];
                $messageBus
                    ->expects($this->once())
                    ->method('dispatch')
                    ->with(static::callback(static function ($dispatchedMessage) use ($nextTable) {
                        return $dispatchedMessage instanceof TruncateMigrationMessage
                            && $dispatchedMessage->getTableName() === $nextTable;
                    }))
                    ->willReturnCallback(static fn ($msg) => new Envelope($msg));
            } else {
                $connection
                    ->expects($this->exactly(2))
                    ->method('executeStatement')
                    ->willReturnCallback(static function ($sql) use ($tableName) {
                        if (str_contains($sql, 'DELETE FROM ' . $tableName)) {
                            return 10;
                        }
                        if (str_contains($sql, 'UPDATE swag_migration_general_setting')) {
                            return 1;
                        }

                        static::fail('Unexpected SQL statement: ' . $sql);
                    });

                $messageBus
                    ->expects($this->never())
                    ->method('dispatch');
            }

            $handler->__invoke($message);
        }
    }

    public function testInvokeWithExactBatchSizeRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_run');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_run LIMIT 100')
            ->willReturn(100);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_run';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithMoreThanBatchSizeRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_media_file');

        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_media_file LIMIT 100')
            ->willReturn(100);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_media_file';
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }
}
