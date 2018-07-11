<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware5\Api;

use SwagMigrationNext\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Migration\MigrationContext;

class Factory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware5.5api';

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function createGateway(MigrationContext $context): Gateway
    {
        $credentials = $context->getCredentials();

        return new Gateway(
            $credentials['endpoint'],
            $credentials['apiUser'],
            $credentials['apiKey']
        );
    }
}
