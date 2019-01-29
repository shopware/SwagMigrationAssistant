<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Exception\ConverterNotFoundException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyMediaFileService;
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
                new ProductConverter(
                    new DummyMappingService(),
                    new ConverterHelperService(),
                    new DummyMediaFileService(),
                    new DummyLoggingService()
                ),
            ])
        );
    }

    public function testGetConverterNotFound(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_TYPE);
        $connection->setProfile($profile);

        $migrationContext = new MigrationContext(
            Uuid::uuid4()->getHex(),
            $connection,
            'foo',
            0,
            250
        );
        try {
            $this->converterRegistry->getConverter($migrationContext);
        } catch (Exception $e) {
            /* @var ConverterNotFoundException $e */
            self::assertInstanceOf(ConverterNotFoundException::class, $e);
            self::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
