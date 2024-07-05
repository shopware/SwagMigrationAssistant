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
interface MediaFileProcessorInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool;

    /**
     * @param MediaProcessWorkloadStruct[] $workload
     *
     * @return MediaProcessWorkloadStruct[]
     */
    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload): array;
}
