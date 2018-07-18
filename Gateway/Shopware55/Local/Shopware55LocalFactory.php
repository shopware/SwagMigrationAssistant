<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local;

use SwagMigrationNext\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Gateway\Shopware55\Local\Reader\Shopware55LocalReaderRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;

class Shopware55LocalFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware55local';

    /**
     * @var Shopware55LocalReaderRegistryInterface
     */
    private $localReaderRegistry;

    public function __construct(Shopware55LocalReaderRegistryInterface $localReaderRegistry)
    {
        $this->localReaderRegistry = $localReaderRegistry;
    }

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function create(MigrationContext $context): GatewayInterface
    {
        $credentials = $context->getCredentials();

        return new Shopware55LocalGateway(
            $this->localReaderRegistry,
            $credentials['dbHost'],
            $credentials['dbPort'] ?? '3306',
            $credentials['dbName'],
            $credentials['dbUser'],
            $credentials['dbPassword']
        );
    }
}
