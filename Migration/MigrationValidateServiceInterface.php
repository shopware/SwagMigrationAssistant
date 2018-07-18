<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;

interface MigrationValidateServiceInterface
{
    /**
     * Validates the converted data
     */
    public function validateData(MigrationContext $migrationContext, Context $context): void;
}
