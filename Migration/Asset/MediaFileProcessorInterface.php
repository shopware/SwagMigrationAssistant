<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContext;

interface MediaFileProcessorInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool;

    public function getSupportedProfileName(): string;

    public function getSupportedGatewayIdentifier(): string;

    public function process(MigrationContext $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array;
}
