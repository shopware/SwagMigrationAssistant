<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface GatewayRegistryInterface
{
    /**
     * @return GatewayInterface[]
     */
    public function getGateways(MigrationContextInterface $migrationContext): array;

    /**
     * Selects the correct gateway by the given migration context
     */
    public function getGateway(MigrationContextInterface $migrationContext): GatewayInterface;
}
