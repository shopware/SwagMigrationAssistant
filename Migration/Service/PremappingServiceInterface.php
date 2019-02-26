<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;

interface PremappingServiceInterface
{
    public function generatePremapping(Context $context, MigrationContext $migrationContext, SwagMigrationRunEntity $run): array;

    public function writePremapping(Context $context, MigrationContext $migrationContext, array $premapping): void;
}
