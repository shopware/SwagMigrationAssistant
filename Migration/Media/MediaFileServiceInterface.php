<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MediaFileServiceInterface
{
    public function writeMediaFile(Context $context): void;

    public function saveMediaFile(array $mediaFile): void;

    public function setWrittenFlag(array $converted, MigrationContextInterface $migrationContext, Context $context): void;
}
