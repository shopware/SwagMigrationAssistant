<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MessageQueue\Message\TruncateMigrationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
#[Package('fundamentals@after-sales')]
/**
 * @internal
 */
final class TruncateMigrationHandler
{
    private const BATCH_SIZE = 250;

    public function __construct(
        private readonly Connection $connection,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(TruncateMigrationMessage $message): void
    {
        $currentStep = 0;
        $tablesToReset = [
            'swag_migration_mapping',
            'swag_migration_logging',
            'swag_migration_data',
            'swag_migration_media_file',
            'swag_migration_run',
            'swag_migration_connection',
        ];

        $step = \array_search(
            $message->getTableName(),
            $tablesToReset,
            true
        );

        if ($step !== false) {
            $currentStep = $step;
        }

        $affectedRows = (int) $this->connection->executeStatement(
            'DELETE FROM ' . $tablesToReset[$currentStep] . ' LIMIT ' . self::BATCH_SIZE
        );

        if ($affectedRows >= self::BATCH_SIZE) {
            $this->bus->dispatch(new TruncateMigrationMessage(
                $tablesToReset[$currentStep]
            ));

            return;
        }

        $nextStep = $currentStep + 1;

        if (isset($tablesToReset[$nextStep])) {
            $this->bus->dispatch(new TruncateMigrationMessage(
                $tablesToReset[$nextStep]
            ));

            return;
        }

        $this->connection->executeStatement(
            'UPDATE swag_migration_general_setting SET `is_reset` = 0;'
        );
    }
}
