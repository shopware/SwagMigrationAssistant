<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
class GatewayRegistry implements GatewayRegistryInterface
{
    /**
     * @param GatewayInterface[] $gateways
     */
    public function __construct(private readonly iterable $gateways)
    {
    }

    /**
     * @return GatewayInterface[]
     */
    public function getGateways(MigrationContextInterface $migrationContext): array
    {
        $profile = $migrationContext->getProfile();

        $gateways = [];
        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($profile)) {
                $gateways[] = $gateway;
            }
        }

        return $gateways;
    }

    public function getGateway(MigrationContextInterface $migrationContext): GatewayInterface
    {
        file_put_contents('/var/www/commercial/custom/plugins/SwagMigrationAssistant/src/migration.connection.log', \var_export("\n\SwagMigrationAssistant\Migration\Gateway\GatewayRegistry::getGateway - STARTS\n", true), FILE_APPEND);
        $connection = $migrationContext->getConnection();
        $profileName = $connection->getProfileName();
        $gatewayName = $connection->getGatewayName();

        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($migrationContext->getProfile()) && $gateway->getName() === $gatewayName) {
                file_put_contents('/var/www/commercial/custom/plugins/SwagMigrationAssistant/src/migration.connection.log', \var_export("\SwagMigrationAssistant\Migration\Gateway\GatewayRegistry::getGateway - returns gateway:\n", true), FILE_APPEND);
                file_put_contents('/var/www/commercial/custom/plugins/SwagMigrationAssistant/src/migration.connection.log', \var_export(get_class($gateway), true), FILE_APPEND);
                file_put_contents('/var/www/commercial/custom/plugins/SwagMigrationAssistant/src/migration.connection.log', \var_export("\n", true), FILE_APPEND);
                return $gateway;
            }
        }

        file_put_contents('/var/www/commercial/custom/plugins/SwagMigrationAssistant/src/migration.connection.log', \var_export("\SwagMigrationAssistant\Migration\Gateway\GatewayRegistry::getGateway - no gateway > exception\n", true), FILE_APPEND);
        throw MigrationException::gatewayNotFound($profileName, $gatewayName);
    }
}
