<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;

interface ProfileInterface
{
    /**
     * Identifier for the profile
     */
    public function getName(): string;

    /**
     * Collects the data from the given gateway and converts it into the internal structure
     * Returns the count of the imported data
     */
    public function convert(array $data, MigrationContextInterface $migrationContext, Context $context): int;

    /**
     * Reads environment information from the given gateway
     */
    public function readEnvironmentInformation(GatewayInterface $gateway): EnvironmentInformation;
}
