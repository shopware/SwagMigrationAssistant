<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\NumberRangeReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class NumberRangeReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var NumberRangeReader
     */
    private $numberRangeReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->numberRangeReader = new NumberRangeReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new NumberRangeDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->numberRangeReader->supports($this->migrationContext));

        $data = $this->numberRangeReader->read($this->migrationContext);

        static::assertCount(9, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertSame('20005', $data[0]['number']);
        static::assertSame('user', $data[0]['name']);
        static::assertSame('de-DE', $data[0]['_locale']);
        static::assertSame('SW', $data[0]['prefix']);

        static::assertSame('920', $data[1]['id']);
        static::assertSame('20002', $data[1]['number']);
        static::assertSame('invoice', $data[1]['name']);
        static::assertSame('de-DE', $data[1]['_locale']);
        static::assertSame('SW', $data[1]['prefix']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->numberRangeReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->numberRangeReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(9, $totalStruct->getTotal());
    }
}
