<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Profile\Dummy;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;

class DummyProfile
{
    public const PROFILE_NAME = 'dummy';

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function collectData(GatewayInterface $gateway, MigrationContextInterface $migrationContext, Context $context): void
    {
    }
}
