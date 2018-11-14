<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Local;

use SwagMigrationNext\Migration\Gateway\GatewayFactoryInterface;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;

class DummyLocalFactory implements GatewayFactoryInterface
{
    public const GATEWAY_NAME = 'shopware55local';

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function create(MigrationContext $context): GatewayInterface
    {
        return new DummyLocalGateway();
    }
}
