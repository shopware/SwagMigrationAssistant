<?php

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;

interface MigrationWriteServiceInterface
{
    /**
     * Writes the converted data into the database
     *
     * @param MigrationContext $migrationContext
     * @param Context $context
     */
    public function writeData(MigrationContext $migrationContext, Context $context): void;
}