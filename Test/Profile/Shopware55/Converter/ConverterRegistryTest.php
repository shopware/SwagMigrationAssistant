<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use Exception;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\ConverterNotFoundException;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
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
        try {
            $this->converterRegistry->getConverter('foo');
        } catch (Exception $e) {
            /* @var ConverterNotFoundException $e */
            self::assertInstanceOf(ConverterNotFoundException::class, $e);
            self::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
