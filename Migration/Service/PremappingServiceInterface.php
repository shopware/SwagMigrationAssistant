<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

interface PremappingServiceInterface
{
    public function generatePremapping(Context $context, MigrationContextInterface $migrationContext, SwagMigrationRunEntity $run): array;

    public function writePremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void;
}
