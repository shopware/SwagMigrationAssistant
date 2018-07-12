<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;

interface ProfileInterface
{
    /**
     * Identifier for the profile
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Collects the data from the given gateway and converts it into the internal structure
     *
     * @param GatewayInterface $gateway
     * @param MigrationContext $migrationContext
     * @param Context          $context
     */
    public function collectData(GatewayInterface $gateway, MigrationContext $migrationContext, Context $context): void;
}
