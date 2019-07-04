<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MediaFileProcessorInterface
{
    public function supports(string $profileName, string $gatewayIdentifier, string $entity): bool;

    public function getSupportedProfileName(): string;

    public function getSupportedGatewayIdentifier(): string;

    public function getSupportedEntity(): string;

    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array;
}
