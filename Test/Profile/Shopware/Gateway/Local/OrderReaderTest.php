<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class OrderReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var OrderReader
     */
    private $orderReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();
        $this->orderReader = new OrderReader(new ConnectionFactory());
        $this->createMigrationContext(0, 10);
    }

    public function testRead(): void
    {
        static::assertTrue($this->orderReader->supports($this->migrationContext));

        $data = $this->orderReader->read($this->migrationContext);

        static::assertCount(2, $data);
        static::assertSame('15', $data[0]['id']);
        static::assertSame('20001', $data[0]['ordernumber']);
        static::assertSame('2', $data[0]['userID']);
        static::assertSame('0', $data[0]['status']);
        static::assertSame('4', $data[0]['paymentID']);
        static::assertSame('9', $data[0]['dispatchID']);
        static::assertSame('EUR', $data[0]['currency']);
        static::assertSame('1', $data[0]['subshopID']);
        static::assertSame('2', $data[0]['customer']['id']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('57', $data[1]['id']);
        static::assertSame('20002', $data[1]['ordernumber']);
        static::assertSame('1', $data[1]['userID']);
        static::assertSame('0', $data[1]['status']);
        static::assertSame('4', $data[1]['paymentID']);
        static::assertSame('9', $data[1]['dispatchID']);
        static::assertSame('EUR', $data[1]['currency']);
        static::assertSame('1', $data[1]['subshopID']);
        static::assertSame('1', $data[1]['customer']['id']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }

    public function testReadWithoutCanceledOrders(): void
    {
        $this->createMigrationContext(0, 1);
        $data = $this->orderReader->read($this->migrationContext);

        static::assertCount(1, $data);
        static::assertSame('15', $data[0]['id']);
        static::assertSame('20001', $data[0]['ordernumber']);
        static::assertSame('2', $data[0]['userID']);
        static::assertSame('0', $data[0]['status']);
        static::assertSame('4', $data[0]['paymentID']);
        static::assertSame('9', $data[0]['dispatchID']);
        static::assertSame('EUR', $data[0]['currency']);
        static::assertSame('1', $data[0]['subshopID']);
        static::assertSame('2', $data[0]['customer']['id']);
        static::assertSame('de-DE', $data[0]['_locale']);

        $this->createMigrationContext(1, 1);
        $data = $this->orderReader->read($this->migrationContext);

        static::assertCount(1, $data);
        static::assertSame('57', $data[0]['id']);
        static::assertSame('20002', $data[0]['ordernumber']);
        static::assertSame('1', $data[0]['userID']);
        static::assertSame('0', $data[0]['status']);
        static::assertSame('4', $data[0]['paymentID']);
        static::assertSame('9', $data[0]['dispatchID']);
        static::assertSame('EUR', $data[0]['currency']);
        static::assertSame('1', $data[0]['subshopID']);
        static::assertSame('1', $data[0]['customer']['id']);
        static::assertSame('de-DE', $data[0]['_locale']);

        $this->createMigrationContext(3, 1);
        $data = $this->orderReader->read($this->migrationContext);

        static::assertCount(0, $data);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->orderReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->orderReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(2, $totalStruct->getTotal());
    }

    private function createMigrationContext(int $offset, int $limit): void
    {
        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new OrderDataSet(),
            $offset,
            $limit
        );
        $this->migrationContext->setGateway(new DummyLocalGateway());
    }
}
