<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderDocumentReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class OrderDocumentReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var OrderDocumentReader
     */
    private $orderDocumentReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->orderDocumentReader = new OrderDocumentReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new OrderDocumentDataSet(),
            0,
            5
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->orderDocumentReader->supports($this->migrationContext));

        $data = $this->orderDocumentReader->read($this->migrationContext);

        static::assertCount(5, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertSame('1', $data[0]['type']);
        static::assertSame('15', $data[0]['orderID']);
        static::assertSame('20001', $data[0]['docID']);
        static::assertSame('1', $data[0]['documenttype']['id']);
        static::assertSame('invoice', $data[0]['documenttype']['key']);

        static::assertSame('2', $data[1]['id']);
        static::assertSame('2', $data[1]['type']);
        static::assertSame('15', $data[1]['orderID']);
        static::assertSame('20001', $data[1]['docID']);
        static::assertSame('2', $data[1]['documenttype']['id']);
        static::assertSame('delivery_note', $data[1]['documenttype']['key']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->orderDocumentReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->orderDocumentReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(8, $totalStruct->getTotal());
    }
}
