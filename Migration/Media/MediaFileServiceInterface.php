<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContextInterface;

interface MediaFileServiceInterface
{
    public function writeMediaFile(Context $context): void;

    public function saveMediaFile(array $mediaFile): void;

    public function setWrittenFlag(array $converted, MigrationContextInterface $migrationContext, Context $context): void;
}
