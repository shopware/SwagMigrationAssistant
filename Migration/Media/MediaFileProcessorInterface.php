<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContextInterface;

interface MediaFileProcessorInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool;

    public function getSupportedProfileName(): string;

    public function getSupportedGatewayIdentifier(): string;

    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array;
}
