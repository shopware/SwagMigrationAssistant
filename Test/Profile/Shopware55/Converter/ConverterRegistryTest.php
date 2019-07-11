<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\ConverterNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;
use Symfony\Component\HttpFoundation\Response;

class ConverterRegistryTest extends TestCase
{
    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    protected function setUp(): void
    {
        $this->converterRegistry = new ConverterRegistry(
            new DummyCollection([
                new Shopware55ProductConverter(
                    new DummyMappingService(),
                    new DummyMediaFileService(),
                    new DummyLoggingService()
                ),
            ])
        );
    }

    public function testGetConverterNotFound(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            Uuid::randomHex(),
            new FooDataSet(),
            0,
            250
        );
        try {
            $this->converterRegistry->getConverter($migrationContext);
        } catch (\Exception $e) {
            /* @var ConverterNotFoundException $e */
            static::assertInstanceOf(ConverterNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
