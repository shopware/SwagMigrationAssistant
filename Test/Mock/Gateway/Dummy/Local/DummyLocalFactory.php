<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local;

use SwagMigrationAssistant\Migration\Gateway\GatewayFactoryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class DummyLocalFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware55local';

    public function supports(string $gatewayIdentifier): bool
    {
        return $gatewayIdentifier === self::GATEWAY_NAME;
    }

    public function create(MigrationContextInterface $context): GatewayInterface
    {
        return new DummyLocalGateway($context);
    }
}
