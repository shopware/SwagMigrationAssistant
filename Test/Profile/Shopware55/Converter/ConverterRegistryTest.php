<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Exception\ConverterNotFoundException;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyMediaFileService;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;
use Symfony\Component\HttpFoundation\Response;

class ConverterRegistryTest extends TestCase
{
    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    protected function setUp()
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
        $migrationContext = new MigrationContext(
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            'foo',
            [],
            0,
            250,
            ''
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
