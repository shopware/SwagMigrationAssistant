<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use Exception;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class ConverterRegistryTest extends KernelTestCase
{
    /**
     * @var ConverterRegistry
     */
    private $converterRegistry;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->converterRegistry = self::$container->get(ConverterRegistry::class);
    }

    public function testGetConverterNotFound(): void
    {
        try {
            $this->converterRegistry->getConverter('foo');
        } catch (Exception $e) {
            /** @var ConverterNotFoundException $e */
            self::assertInstanceOf(ConverterNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
