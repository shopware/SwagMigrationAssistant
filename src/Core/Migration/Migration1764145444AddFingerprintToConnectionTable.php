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

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1764145444AddFingerprintToConnectionTable extends MigrationStep
{
    public const TABLE = 'swag_migration_connection';

    public const COLUMN = 'source_system_fingerprint';

    public function getCreationTimestamp(): int
    {
        return 1764145444;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: self::TABLE,
            column: self::COLUMN,
            type: 'VARCHAR(255)',
        );
    }
}
