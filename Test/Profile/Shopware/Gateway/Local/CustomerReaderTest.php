<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class CustomerReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var CustomerReader
     */
    private $customerReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->customerReader = new CustomerReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CustomerDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->customerReader->supports($this->migrationContext));

        $data = $this->customerReader->read($this->migrationContext);

        static::assertCount(3, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertSame('md5', $data[0]['encoder']);
        static::assertSame('1', $data[0]['default_billing_address_id']);
        static::assertSame('3', $data[0]['default_shipping_address_id']);
        static::assertSame('prepayment', $data[0]['defaultpayment']['name']);
        static::assertSame('de-DE', $data[0]['customerlanguage']['locale']);
        static::assertCount(2, $data[0]['addresses']);

        static::assertSame('2', $data[1]['id']);
        static::assertSame('md5', $data[1]['encoder']);
        static::assertSame('2', $data[1]['default_billing_address_id']);
        static::assertSame('4', $data[1]['default_shipping_address_id']);
        static::assertSame('invoice', $data[1]['defaultpayment']['name']);
        static::assertSame('de-DE', $data[1]['customerlanguage']['locale']);
        static::assertCount(2, $data[1]['addresses']);

        static::assertSame('3', $data[2]['id']);
        static::assertSame('bcrypt', $data[2]['encoder']);
        static::assertSame('5', $data[2]['default_billing_address_id']);
        static::assertSame('5', $data[2]['default_shipping_address_id']);
        static::assertSame('prepayment', $data[2]['defaultpayment']['name']);
        static::assertSame('de-DE', $data[2]['customerlanguage']['locale']);
        static::assertCount(1, $data[2]['addresses']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->customerReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->customerReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(3, $totalStruct->getTotal());
    }
}
