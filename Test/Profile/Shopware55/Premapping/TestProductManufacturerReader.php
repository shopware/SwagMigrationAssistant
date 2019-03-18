<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Premapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Profile\Shopware55\Premapping\ProductManufacturerReader;

class TestProductManufacturerReader extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var ProductManufacturerReader
     */
    private $productManufacturerReader;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $productManufacturer = $this->getContainer()->get('product_manufacturer.repository');
        $this->productManufacturerReader = new ProductManufacturerReader($productManufacturer);
    }

    public function testGetMapping(): void
    {
        $migrationContext = new MigrationContext();

        /** @var PremappingStruct $premapping */
        $premapping = $this->productManufacturerReader->getPremapping($this->context, $migrationContext);

        static::assertCount(1, $premapping->getChoices());
        static::assertCount(1, $premapping->getMapping());
        static::assertSame('default_manufacturer', $premapping->getMapping()[0]->getSourceId());
        static::assertSame(ProductManufacturerReader::getMappingName(), $premapping->getEntity());
    }
}
