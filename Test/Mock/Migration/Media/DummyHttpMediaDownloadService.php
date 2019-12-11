<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class DummyHttpMediaDownloadService implements MediaFileProcessorInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === MediaDataSet::getEntity();
    }

    public function process(
        MigrationContextInterface $migrationContext,
        Context $context,
        array $workload,
        int $fileChunkByteSize
    ): array {
        return $workload;
    }
}
