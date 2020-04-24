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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CurrencyReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class CurrencyReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var CurrencyReader
     */
    private $currencyReader;

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

        $this->currencyReader = new CurrencyReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CurrencyDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->currencyReader->supports($this->migrationContext));

        $data = $this->currencyReader->read($this->migrationContext);

        static::assertCount(2, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertSame('EUR', $data[0]['currency']);
        static::assertSame('1', $data[0]['standard']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('2', $data[1]['id']);
        static::assertSame('USD', $data[1]['currency']);
        static::assertSame('0', $data[1]['standard']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->currencyReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->currencyReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(2, $totalStruct->getTotal());
    }
}
