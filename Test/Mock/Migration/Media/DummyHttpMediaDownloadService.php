<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Media\AbstractMediaFileProcessor;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class DummyHttpMediaDownloadService extends AbstractMediaFileProcessor
{
    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function getSupportedGatewayIdentifier(): string
    {
        return ShopwareApiGateway::GATEWAY_NAME;
    }

    public function getSupportedEntity(): string
    {
        return MediaDataSet::getEntity();
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
