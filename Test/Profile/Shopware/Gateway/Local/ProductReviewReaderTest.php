<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductReviewDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductReviewReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class ProductReviewReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var ProductReviewReader
     */
    private $productReviewReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->productReviewReader = new ProductReviewReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ProductReviewDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->productReviewReader->supports($this->migrationContext));

        $data = $this->productReviewReader->read($this->migrationContext);

        static::assertCount(2, $data);
        static::assertSame('4', $data[0]['id']);
        static::assertSame('198', $data[0]['articleID']);
        static::assertSame('', $data[0]['email']);
        static::assertSame('1', $data[0]['mainShopId']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('12', $data[1]['id']);
        static::assertSame('198', $data[1]['articleID']);
        static::assertSame('', $data[1]['email']);
        static::assertSame('1', $data[1]['mainShopId']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->productReviewReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->productReviewReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(2, $totalStruct->getTotal());
    }
}
