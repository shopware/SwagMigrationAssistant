<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

interface PremappingServiceInterface
{
    /**
     * @return PremappingStruct[]
     */
    public function generatePremapping(Context $context, MigrationContextInterface $migrationContext, SwagMigrationRunEntity $run): array;

    public function writePremapping(Context $context, MigrationContextInterface $migrationContext, array $premapping): void;
}
