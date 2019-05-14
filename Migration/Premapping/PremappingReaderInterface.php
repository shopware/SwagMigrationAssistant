<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Premapping;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContext;

interface PremappingReaderInterface
{
    public static function getMappingName(): string;

    /**
     * @param string[] $entityGroupNames
     */
    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool;

    public function getPremapping(Context $context, MigrationContext $migrationContext): PremappingStruct;
}
