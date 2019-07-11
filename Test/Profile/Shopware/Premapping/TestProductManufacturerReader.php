<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware\Premapping;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Premapping\ProductManufacturerReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

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
        $migrationContext = new MigrationContext(new Shopware55Profile());

        /** @var PremappingStruct $premapping */
        $premapping = $this->productManufacturerReader->getPremapping($this->context, $migrationContext);

        static::assertCount(1, $premapping->getChoices());
        static::assertCount(1, $premapping->getMapping());
        static::assertSame('default_manufacturer', $premapping->getMapping()[0]->getSourceId());
        static::assertSame(ProductManufacturerReader::getMappingName(), $premapping->getEntity());
    }
}
