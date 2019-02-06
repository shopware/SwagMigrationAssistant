<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Media\AbstractMediaFileProcessor;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class DummyHttpMediaDownloadService extends AbstractMediaFileProcessor
{
    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function getSupportedGatewayIdentifier(): string
    {
        return Shopware55ApiGateway::GATEWAY_TYPE;
    }

    public function process(
        MigrationContext $migrationContext,
        Context $context,
        array $workload,
        int $fileChunkByteSize
    ): array {
        return $workload;
    }
}
