<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local;

use SwagMigrationNext\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;

class Shopware55LocalFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware55local';

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function create(MigrationContext $context): GatewayInterface
    {
        $credentials = $context->getCredentials();

        return new Shopware55LocalGateway(
            $credentials['dbHost'],
            $credentials['dbPort'] ?? '3306',
            $credentials['dbName'],
            $credentials['dbUser'],
            $credentials['dbPassword']
        );
    }
}
