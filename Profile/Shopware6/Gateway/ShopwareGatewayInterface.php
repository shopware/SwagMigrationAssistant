<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface ShopwareGatewayInterface extends GatewayInterface
{
    /**
     * @param array<string, mixed> $filter
     *
     * @return array<string, mixed>
     */
    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array;
}
