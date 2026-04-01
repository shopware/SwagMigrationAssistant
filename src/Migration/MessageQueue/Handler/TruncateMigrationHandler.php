<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\ErrorResolution\Entity\SwagMigrationFixDefinition;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;
use SwagMigrationAssistant\Migration\MessageQueue\Message\TruncateMigrationMessage;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('fundamentals@after-sales')]
final class TruncateMigrationHandler
{
    final public const TABLE_TO_TRUNCATE = [
        SwagMigrationMappingDefinition::ENTITY_NAME,
        SwagMigrationLoggingDefinition::ENTITY_NAME,
        SwagMigrationDataDefinition::ENTITY_NAME,
        SwagMigrationMediaFileDefinition::ENTITY_NAME,
        SwagMigrationFixDefinition::ENTITY_NAME,
        SwagMigrationRunDefinition::ENTITY_NAME,
        SwagMigrationConnectionDefinition::ENTITY_NAME,
    ];

    private const BATCH_SIZE = 250;

    public function __construct(
        private readonly Connection $connection,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(TruncateMigrationMessage $message): void
    {
        $currentStep = 0;

        $step = \array_search(
            $message->getTableName(),
            self::TABLE_TO_TRUNCATE,
            true
        );

        if ($step !== false) {
            $currentStep = $step;
        }

        $currentTable = self::TABLE_TO_TRUNCATE[$currentStep];

        $affectedRows = (int) $this->connection->executeStatement(
            'DELETE FROM ' . $currentTable . ' LIMIT ' . self::BATCH_SIZE
        );

        if ($affectedRows >= self::BATCH_SIZE) {
            $this->bus->dispatch(new TruncateMigrationMessage(
                $currentTable,
            ));

            return;
        }

        $nextTable = self::TABLE_TO_TRUNCATE[$currentStep + 1] ?? null;

        if ($nextTable !== null) {
            $this->bus->dispatch(new TruncateMigrationMessage(
                $nextTable
            ));

            return;
        }

        $this->connection->executeStatement(
            'UPDATE swag_migration_general_setting SET `is_reset` = 0;'
        );
    }
}
