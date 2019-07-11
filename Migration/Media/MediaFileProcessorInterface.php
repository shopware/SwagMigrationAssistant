<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MediaFileProcessorInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool;

    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array;
}
