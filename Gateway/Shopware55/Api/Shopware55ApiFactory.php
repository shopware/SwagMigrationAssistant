<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api;

use SwagMigrationNext\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;

class Shopware55ApiFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware55api';

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function create(MigrationContext $context): GatewayInterface
    {
        $credentials = $context->getCredentials();

        return new Shopware55ApiGateway(
            $credentials['endpoint'],
            $credentials['apiUser'],
            $credentials['apiKey']
        );
    }
}
