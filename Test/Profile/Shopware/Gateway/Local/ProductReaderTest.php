<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class ProductReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var ProductReader
     */
    private $productReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->productReader = new ProductReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ProductDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->productReader->supports($this->migrationContext));

        $data = $this->productReader->read($this->migrationContext);

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
        static::assertTrue($this->productReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->productReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(401, $totalStruct->getTotal());
    }
}
