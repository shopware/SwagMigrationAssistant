<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;

interface ProfileInterface
{
    public function getName(): string;

    public function getData(GatewayInterface $gateway, MigrationContext $context): array;
}
