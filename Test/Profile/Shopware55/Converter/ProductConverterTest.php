<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use Exception;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Profile\Shopware55\Converter\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;
use Symfony\Component\HttpFoundation\Response;

class ProductConverterTest extends TestCase
{
    public function testConvertNoParent()
    {
        $productConverter = new ProductConverter(new DummyMappingService(), new ConverterHelperService());

        $testData = [
            'id' => '123',
            'detail' => ['kind' => 2],
            'configurator_set_id' => 1,
        ];

        try {
            $productConverter->convert($testData);
        } catch (Exception $e) {
            /* @var ParentEntityForChildNotFoundException $e */
            self::assertInstanceOf(ParentEntityForChildNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
