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
#[Package('core')]
class Migration1720008983TruncateOutdatedProgressStructureFromRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1720008983;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE swag_migration_run SET progress = NULL WHERE progress NOT LIKE \'%currentEntityProgress%\'');
    }
}
