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
class Migration1701426268RenameProfileInConnections extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1701426268;
    }

    public function update(Connection $connection): void
    {
        $connection->update(
            'swag_migration_connection',
            ['profile_name' => 'shopware6major'],
            ['profile_name' => 'shopware63']
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
