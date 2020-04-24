<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Exception\GatewayNotFoundException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class GatewayRegistry implements GatewayRegistryInterface
{
    /**
     * @var GatewayInterface[]
     */
    private $gateways;

    /**
     * @param GatewayInterface[] $gateways
     */
    public function __construct(iterable $gateways)
    {
        $this->gateways = $gateways;
    }

    /**
     * @throws GatewayNotFoundException
     *
     * @return GatewayInterface[]
     */
    public function getGateways(MigrationContextInterface $migrationContext): array
    {
        $gateways = [];
        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($migrationContext)) {
                $gateways[] = $gateway;
            }
        }

        return $gateways;
    }

    /**
     * @throws GatewayNotFoundException
     */
    public function getGateway(MigrationContextInterface $migrationContext): GatewayInterface
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            throw new MigrationContextPropertyMissingException('Connection');
        }

        $profileName = $connection->getProfileName();
        $gatewayName = $connection->getGatewayName();

        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($migrationContext) && $gateway->getName() === $gatewayName) {
                return $gateway;
            }
        }

        throw new GatewayNotFoundException($profileName . '-' . $gatewayName);
    }
}
