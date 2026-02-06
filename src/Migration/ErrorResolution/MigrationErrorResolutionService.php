<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\ErrorResolution;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\ErrorResolution\Event\MigrationPostErrorResolutionEvent;
use SwagMigrationAssistant\Migration\ErrorResolution\Event\MigrationPreErrorResolutionEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
readonly class MigrationErrorResolutionService
{
    public function __construct(
        private Connection $connection,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param array<int|string, array<int|string, mixed>> $data
     */
    public function applyFixes(array &$data, string $connectionId, string $runId, Context $context): void
    {
        $emptyFixes = [];
        $errorResolutionContext = new MigrationErrorResolutionContext(
            $data,
            $emptyFixes,
            $connectionId,
            $runId,
            $context,
        );

        $this->loadFixes($errorResolutionContext);

        if (empty($errorResolutionContext->getFixes())) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new MigrationPreErrorResolutionEvent($errorResolutionContext),
        );

        $fixes = $errorResolutionContext->getFixes();
        $data = $errorResolutionContext->getData();

        foreach ($data as &$item) {
            $id = $item['id'];

            if (!\array_key_exists($id, $fixes) || !\is_array($fixes[$id])) {
                continue;
            }

            foreach ($fixes[$id] as $fix) {
                $fix->apply($item);
            }
        }

        unset($item);

        $errorResolutionContext->setData($data);

        $this->eventDispatcher->dispatch(
            new MigrationPostErrorResolutionEvent($errorResolutionContext),
        );

        $data = $errorResolutionContext->getData();
    }

    /**
     * Loads fixes from the database and populates them in the context.
     */
    private function loadFixes(MigrationErrorResolutionContext $errorResolutionContext): void
    {
        $itemIds = \array_column($errorResolutionContext->getData(), 'id');

        if (empty($itemIds)) {
            $errorResolutionContext->setFixes([]);

            return;
        }

        // To ensure, only select fixes for the current run, join swag_migration_logging table and filter by run_id
        $sql = <<<'SQL'
SELECT fix.entity_id AS entityId, fix.id, fix.value, fix.path
FROM swag_migration_fix AS fix
INNER JOIN swag_migration_logging AS log ON log.entity_id = fix.entity_id
WHERE fix.entity_id IN (:ids)
AND fix.connection_id = :connectionId
AND log.run_id = :runId
AND log.user_fixable = 1;
SQL;

        $result = $this->connection->fetchAllAssociative(
            $sql,
            [
                'ids' => Uuid::fromHexToBytesList($itemIds),
                'connectionId' => Uuid::fromHexToBytes($errorResolutionContext->getConnectionId()),
                'runId' => Uuid::fromHexToBytes($errorResolutionContext->getRunId()),
            ],
            [
                'ids' => ArrayParameterType::STRING,
            ]
        );

        $fixes = [];
        foreach ($result as $row) {
            $entityId = Uuid::fromBytesToHex($row['entityId']);

            if (!\array_key_exists($entityId, $fixes)) {
                $fixes[$entityId] = [];
            }

            $fixes[$entityId][] = MigrationFix::fromDatabaseQuery($row);
        }

        $errorResolutionContext->setFixes($fixes);
    }
}
