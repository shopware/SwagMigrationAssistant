<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class DummyMediaFileService extends MediaFileService
{
    public function __construct()
    {
    }

    public function writeMediaFile(Context $context): void
    {
    }

    public function setWrittenFlag(array $converted, MigrationContextInterface $migrationContext, Context $context): void
    {
    }

    public function getMediaFileArray(): array
    {
        return $this->writeArray;
    }

    public function resetMediaFileArray(): void
    {
        $this->writeArray = [];
    }
}
