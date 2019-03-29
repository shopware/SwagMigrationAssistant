<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Media;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\ProcessorNotFoundException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Media\MediaFileProcessorRegistry;
use SwagMigrationNext\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyHttpMediaDownloadService;
use SwagMigrationNext\Test\Profile\Shopware55\DataSet\FooDataSet;
use Symfony\Component\HttpFoundation\Response;

class MediaFileProcessorRegistryTest extends TestCase
{
    /**
     * @var MediaFileProcessorRegistryInterface
     */
    private $processorRegistry;

    protected function setUp(): void
    {
        $this->processorRegistry = new MediaFileProcessorRegistry(
            new DummyCollection(
                [
                    new DummyHttpMediaDownloadService(),
                ]
            )
        );
    }

    public function testGetProcessorNotFound(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);

        $connection->setProfile($profile);
        $connection->setCredentialFields([]);

        $context = new MigrationContext(
            $connection,
            '',
            new FooDataSet()
        );

        try {
            $this->processorRegistry->getProcessor($context);
        } catch (\Exception $e) {
            /* @var ProcessorNotFoundException $e */
            static::assertInstanceOf(ProcessorNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
