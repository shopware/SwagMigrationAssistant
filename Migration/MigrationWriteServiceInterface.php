<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;

interface MigrationWriteServiceInterface
{
    /**
     * Writes the converted data into the database
     */
    public function writeData(MigrationContext $migrationContext, Context $context): void;
}
