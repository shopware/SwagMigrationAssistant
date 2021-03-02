<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Message\CleanupMigrationMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class CleanupMigrationHandler extends AbstractMessageHandler
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(Connection $connection, MessageBusInterface $bus)
    {
        $this->connection = $connection;
        $this->bus = $bus;
    }

    /**
     * @param CleanupMigrationMessage $message
     */
    public function handle($message): void
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

        $step = \array_search($message->getTableName(), $tablesToReset, true);
        if ($step !== false) {
            $currentStep = $step;
        }

        $nextStep = $currentStep + 1;
        if (isset($tablesToReset[$nextStep])) {
            $nextMessage = new CleanupMigrationMessage($tablesToReset[$nextStep]);
            $this->bus->dispatch($nextMessage);
        }
        $this->connection->executeStatement('DELETE FROM ' . $tablesToReset[$currentStep] . ';');
    }

    public static function getHandledMessages(): iterable
    {
        return [
            CleanupMigrationMessage::class,
        ];
    }
}
