<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local;

use SwagMigrationNext\Migration\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;

class Shopware55LocalFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware55local';

    public function supports(string $gatewayIdentifier): bool
    {
        return $gatewayIdentifier === self::GATEWAY_NAME;
    }

    public function create(MigrationContext $context): GatewayInterface
    {
        return new Shopware55LocalGateway($context);
    }
}
