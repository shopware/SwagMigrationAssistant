<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

#[Package('fundamentals@after-sales')]
class Migration1759000000AddIsResettingChecksumsToSetting extends MigrationStep
{
    public const TABLE = 'swag_migration_general_setting';

    public const COLUMN = 'is_resetting_checksums';

    public function getCreationTimestamp(): int
    {
        return 1759000000;
    }

    public function update(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns(self::TABLE);

        if (isset($columns[self::COLUMN])) {
            return;
        }

        $connection->executeStatement(\sprintf(
            'ALTER TABLE %s ADD COLUMN %s TINYINT(1) NOT NULL DEFAULT 0',
            self::TABLE,
            self::COLUMN
        ));
    }
}
