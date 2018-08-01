<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Gateway\GatewayInterface;

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
    public function collectData(GatewayInterface $gateway, string $entityName, Context $context, int $offset, int $limit): int;
}
