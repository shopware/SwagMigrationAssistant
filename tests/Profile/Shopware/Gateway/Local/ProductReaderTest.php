<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\AbstractReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductReader;
use SwagMigrationAssistant\Test\LocalConnectionTestCase;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('services-settings')]
class ProductReaderTest extends LocalConnectionTestCase
{
    public function testReadEsdProduct(): void
    {
        $this->setLimitAndOffset(1, 224);
        $this->getMigrationContext()->setGateway(new DummyLocalGateway());

        $productReader = $this->getProductReader();
        static::assertTrue($productReader->supports($this->getMigrationContext()));

        $data = $productReader->read($this->getMigrationContext());

        static::assertArrayHasKey('esdFiles', $data[0]);
        static::assertSame(['articledetailsID', 'id', 'name', 'path'], \array_keys($data[0]['esdFiles'][0]));
    }

    public function testRead(): void
    {
        $productReader = $this->getProductReader();
        static::assertTrue($productReader->supports($this->getMigrationContext()));

        $data = $productReader->read($this->getMigrationContext());

        static::assertCount(10, $data);
        static::assertSame('3', $data[0]['detail']['id']);
        static::assertSame('3', $data[0]['detail']['articleID']);
        static::assertSame('SW10003', $data[0]['detail']['ordernumber']);
        static::assertSame('3', $data[0]['id']);
        static::assertSame('2', $data[0]['supplierID']);
        static::assertSame('de-DE', $data[0]['_locale']);
        static::assertCount(3, $data[0]['categories']);
        static::assertSame('14', $data[0]['categories'][0]['id']);
        static::assertSame('21', $data[0]['categories'][1]['id']);
        static::assertSame('50', $data[0]['categories'][2]['id']);
        static::assertSame('50', $data[0]['categories'][2]['id']);
        static::assertSame('3', $data[0]['prices'][0]['id']);
        static::assertSame('EK', $data[0]['prices'][0]['customergroup']['groupkey']);
        static::assertSame('1029', $data[0]['prices'][1]['id']);
        static::assertSame('H', $data[0]['prices'][1]['customergroup']['groupkey']);

        static::assertSame('4', $data[1]['detail']['id']);
        static::assertSame('4', $data[1]['detail']['articleID']);
        static::assertSame('SW10004', $data[1]['detail']['ordernumber']);
        static::assertSame('4', $data[1]['id']);
        static::assertSame('2', $data[1]['supplierID']);
        static::assertSame('de-DE', $data[1]['_locale']);
        static::assertCount(4, $data[1]['categories']);
        static::assertSame('14', $data[1]['categories'][0]['id']);
        static::assertSame('21', $data[1]['categories'][1]['id']);
        static::assertSame('50', $data[1]['categories'][2]['id']);
        static::assertSame('50', $data[1]['categories'][2]['id']);
        static::assertSame('67', $data[1]['categories'][3]['id']);
        static::assertSame('4', $data[1]['prices'][0]['id']);
        static::assertSame('EK', $data[1]['prices'][0]['customergroup']['groupkey']);
    }

    public function testReadTotal(): void
    {
        $productReader = $this->getProductReader();
        static::assertTrue($productReader->supportsTotal($this->getMigrationContext()));

        $totalStruct = $productReader->readTotal($this->getMigrationContext());
        static::assertInstanceOf(TotalStruct::class, $totalStruct);
        $dataSet = $this->getMigrationContext()->getDataSet();
        static::assertInstanceOf(DataSet::class, $dataSet);

        static::assertSame($dataSet::getEntity(), $totalStruct->getEntityName());
        static::assertSame(401, $totalStruct->getTotal());
    }

    public function testGetProductsShouldAddShopsCorrectly(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/subshops.sql');
        static::assertIsString($sql);

        $this->getExternalConnection()->executeStatement($sql);

        $this->setLimitAndOffset(1, 122);

        $products = $this->getProductReader()->read($this->getMigrationContext());

        static::assertCount(1, $products);
        static::assertSame(['1', '3', '4'], $products[0]['shops']);
    }

    public function testGetProductsShouldAddMainShopOfLanguageShop(): void
    {
        $connection = $this->getExternalConnection();

        $sql = \file_get_contents(__DIR__ . '/_fixtures/language_shop.sql');
        static::assertIsString($sql);
        $connection->executeStatement($sql);

        $this->setLimitAndOffset(500, 0);

        // Get product wich is only assigned to a language shop
        $products = $this->getProductReader()->read($this->getMigrationContext());
        $product = $this->getProductById(20273, $products);

        static::assertSame('Some French cool name', $product['name']);
        // Expect getting the main shop of the language shop
        static::assertSame(['3'], $product['shops']);
    }

    protected function getDataSet(): DataSet
    {
        return new ProductDataSet();
    }

    private function getProductReader(): AbstractReader
    {
        return new ProductReader($this->getConnectionFactory());
    }

    /**
     * @param array<int, mixed> $products
     *
     * @return array<string, mixed>
     */
    private function getProductById(int $id, array $products): array
    {
        foreach ($products as $product) {
            if ((int) $product['id'] === $id) {
                return $product;
            }
        }

        return [];
    }
}
