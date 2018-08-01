<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;

interface MigrationCollectServiceInterface
{
    /**
     * Uses the given migration context and the shopware context to read data from an external source
     * and tries to convert it into the internal structure.
     * Returns the count of the imported data
     */
    public function fetchData(MigrationContext $migrationContext, Context $context): int;
}
