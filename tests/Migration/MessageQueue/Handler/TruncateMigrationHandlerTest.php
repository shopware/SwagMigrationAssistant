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
use SwagMigrationAssistant\Migration\MessageQueue\Handler\TruncateMigrationHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Message\TruncateMigrationMessage;
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
            $this->messageBus
        );
    }

    public function testInvokeWithNullTableNameStartsFromFirstTable(): void
    {
        $message = new TruncateMigrationMessage(null);

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_mapping LIMIT 250')
            ->willReturn(100);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_logging';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFirstTableAndFullBatchRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_mapping');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_mapping LIMIT 250')
            ->willReturn(250);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_mapping';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFirstTableAndPartialBatchDispatchesNextTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_mapping');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_mapping LIMIT 250')
            ->willReturn(100);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_logging';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithMiddleTableAndFullBatchRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_data');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_data LIMIT 250')
            ->willReturn(250);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_data';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithMiddleTableAndPartialBatchDispatchesNextTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_data');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_data LIMIT 250')
            ->willReturn(50);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_media_file';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithLastTableAndFullBatchRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_connection');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_connection LIMIT 250')
            ->willReturn(250);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_connection';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithLastTableAndPartialBatchUpdatesGeneralSetting(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_connection');

        $this->connection
            ->expects(static::exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(function ($sql) {
                if (str_contains($sql, 'DELETE FROM swag_migration_connection')) {
                    return 100;
                }
                if (str_contains($sql, 'UPDATE swag_migration_general_setting')) {
                    static::assertStringContainsString('`is_reset` = 0', $sql);

                    return 1;
                }

                static::fail('Unexpected SQL statement: ' . $sql);
            });

        $this->messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithZeroAffectedRowsMovesToNextTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_logging');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_logging LIMIT 250')
            ->willReturn(0);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_data';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeProcessesAllTablesInCorrectOrder(): void
    {
        $expectedTables = [
            'swag_migration_mapping',
            'swag_migration_logging',
            'swag_migration_data',
            'swag_migration_media_file',
            'swag_migration_run',
            'swag_migration_connection',
        ];

        foreach ($expectedTables as $index => $tableName) {
            $message = new TruncateMigrationMessage($tableName);

            $connection = $this->createMock(Connection::class);
            $messageBus = $this->createMock(MessageBusInterface::class);
            $handler = new TruncateMigrationHandler($connection, $messageBus);

            if ($index < \count($expectedTables) - 1) {
                $connection
                    ->expects(static::once())
                    ->method('executeStatement')
                    ->with('DELETE FROM ' . $tableName . ' LIMIT 250')
                    ->willReturn(10);

                $nextTable = $expectedTables[$index + 1];
                $messageBus
                    ->expects(static::once())
                    ->method('dispatch')
                    ->with(static::callback(function ($dispatchedMessage) use ($nextTable) {
                        return $dispatchedMessage instanceof TruncateMigrationMessage
                            && $dispatchedMessage->getTableName() === $nextTable;
                    }))
                    ->willReturnCallback(fn ($msg) => new Envelope($msg));
            } else {
                $connection
                    ->expects(static::exactly(2))
                    ->method('executeStatement')
                    ->willReturnCallback(function ($sql) use ($tableName) {
                        if (str_contains($sql, 'DELETE FROM ' . $tableName)) {
                            return 10;
                        }
                        if (str_contains($sql, 'UPDATE swag_migration_general_setting')) {
                            return 1;
                        }

                        static::fail('Unexpected SQL statement: ' . $sql);
                    });

                $messageBus
                    ->expects(static::never())
                    ->method('dispatch');
            }

            $handler->__invoke($message);
        }
    }

    public function testInvokeWithExactBatchSizeRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_run');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_run LIMIT 250')
            ->willReturn(250);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_run';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithMoreThanBatchSizeRedispatchesSameTable(): void
    {
        $message = new TruncateMigrationMessage('swag_migration_media_file');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with('DELETE FROM swag_migration_media_file LIMIT 250')
            ->willReturn(250);

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) {
                return $dispatchedMessage instanceof TruncateMigrationMessage
                    && $dispatchedMessage->getTableName() === 'swag_migration_media_file';
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }
}
