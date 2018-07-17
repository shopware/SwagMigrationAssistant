<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api;

use SwagMigrationNext\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderRegistry;
use SwagMigrationNext\Migration\MigrationContext;

class Shopware55ApiFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware55api';

    /**
     * @var Shopware55ApiReaderRegistry
     */
    private $apiReaderRegistry;

    public function __construct(Shopware55ApiReaderRegistry $apiReaderRegistry)
    {
        $this->apiReaderRegistry = $apiReaderRegistry;
    }

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function create(MigrationContext $context): GatewayInterface
    {
        $credentials = $context->getCredentials();

        return new Shopware55ApiGateway(
            $this->apiReaderRegistry,
            $credentials['endpoint'],
            $credentials['apiUser'],
            $credentials['apiKey']
        );
    }
}
