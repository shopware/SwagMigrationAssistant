<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface MediaFileServiceInterface
{
    public function writeMediaFile(Context $context): void;

    public function saveMediaFile(array $mediaFile): void;

    public function setWrittenFlag(array $converted, MigrationContextInterface $migrationContext, Context $context): void;
}
