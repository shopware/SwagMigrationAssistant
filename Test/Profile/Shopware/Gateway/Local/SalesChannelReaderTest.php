<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\SalesChannelReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class SalesChannelReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var SalesChannelReader
     */
    private $salesChannelReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->salesChannelReader = new SalesChannelReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new SalesChannelDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->salesChannelReader->supports($this->migrationContext));

        $data = $this->salesChannelReader->read($this->migrationContext);

        static::assertCount(1, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertNull($data[0]['main_id']);
        static::assertSame('Deutsch', $data[0]['name']);
        static::assertSame('sw55.local', $data[0]['host']);
        static::assertSame('3', $data[0]['category_id']);
        static::assertSame('de-DE', $data[0]['locale']);
        static::assertSame('de-DE', $data[0]['_locale']);
        static::assertSame('EUR', $data[0]['currency']);

        static::assertCount(1, $data[0]['children']);
        static::assertSame('39', $data[0]['children'][0]['category_id']);
        static::assertSame('en-GB', $data[0]['children'][0]['locale']);
        static::assertSame('de-DE', $data[0]['children'][0]['_locale']);
        static::assertSame('EUR', $data[0]['children'][0]['currency']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->salesChannelReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->salesChannelReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(2, $totalStruct->getTotal());
    }
}
