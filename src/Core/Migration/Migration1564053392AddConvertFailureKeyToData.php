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

#[Package('services-settings')]
class Migration1564053392AddConvertFailureKeyToData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1564053392;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE swag_migration_data ADD INDEX `idx.swag_migration_data.entity__run_id__convert_failure` (`entity`, `run_id`, `convert_failure`);');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
