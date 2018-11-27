<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContext;

interface MigrationDataWriterInterface
{
    /**
     * Writes the converted data into the database
     */
    public function writeData(MigrationContext $migrationContext, Context $context): void;
}
