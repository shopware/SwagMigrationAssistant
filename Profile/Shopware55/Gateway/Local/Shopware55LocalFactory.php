<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local;

use SwagMigrationNext\Migration\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class Shopware55LocalFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = Shopware55Profile::PROFILE_NAME . Shopware55LocalGateway::GATEWAY_NAME;

    public function supports(string $gatewayIdentifier): bool
    {
        return $gatewayIdentifier === self::GATEWAY_NAME;
    }

    public function create(MigrationContextInterface $context): GatewayInterface
    {
        return new Shopware55LocalGateway($context);
    }
}
