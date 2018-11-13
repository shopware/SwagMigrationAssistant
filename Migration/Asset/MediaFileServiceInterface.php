<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContext;

interface MediaFileServiceInterface
{
    public function writeMediaFile(Context $context): void;

    public function saveMediaFile(array $mediaFile): void;

    public function setWrittenFlag(array $converted, MigrationContext $migrationContext, Context $context): void;
}
