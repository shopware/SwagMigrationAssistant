<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;

interface ProfileInterface
{
    /**
     * Identifier for the profile
     */
    public function getName(): string;

    /**
     * Collects the data from the given gateway and converts it into the internal structure
     */
    public function collectData(GatewayInterface $gateway, MigrationContext $migrationContext, Context $context): void;
}
