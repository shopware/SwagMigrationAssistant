<?php

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;

interface MigrationWriteServiceInterface
{
    public function writeData(MigrationContext $migrationContext, Context $context): void;
}