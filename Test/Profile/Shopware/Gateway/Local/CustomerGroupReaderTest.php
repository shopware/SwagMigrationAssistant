<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerGroupReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class CustomerGroupReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var CustomerGroupReader
     */
    private $customerGroupReader;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->customerGroupReader = new CustomerGroupReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CustomerGroupDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->customerGroupReader->supports($this->migrationContext));

        $data = $this->customerGroupReader->read($this->migrationContext);

        static::assertCount(2, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertSame('EK', $data[0]['groupkey']);
        static::assertSame('de-DE', $data[0]['_locale']);
        static::assertSame('1', $data[0]['tax']);
        static::assertSame('1', $data[0]['taxinput']);

        static::assertSame('2', $data[1]['id']);
        static::assertSame('H', $data[1]['groupkey']);
        static::assertSame('de-DE', $data[1]['_locale']);
        static::assertSame('0', $data[1]['tax']);
        static::assertSame('0', $data[1]['taxinput']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->customerGroupReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->customerGroupReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(2, $totalStruct->getTotal());
    }
}
