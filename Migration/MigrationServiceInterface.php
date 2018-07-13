<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;

interface MigrationServiceInterface
{
    /**
     * Uses the given migration context and the shopware context to read data from an external source
     * and tries to convert it into the internal structure.
     *
     * @param MigrationContext $migrationContext
     * @param Context          $context
     */
    public function fetchData(MigrationContext $migrationContext, Context $context): void;
}
