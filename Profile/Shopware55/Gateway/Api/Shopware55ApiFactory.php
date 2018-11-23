<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api;

use SwagMigrationNext\Migration\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class Shopware55ApiFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = Shopware55Profile::PROFILE_NAME . Shopware55ApiGateway::GATEWAY_TYPE;

    public function supports(string $gatewayIdentifier): bool
    {
        return $gatewayIdentifier === self::GATEWAY_NAME;
    }

    public function create(MigrationContext $context): GatewayInterface
    {
        return new Shopware55ApiGateway($context);
    }
}
