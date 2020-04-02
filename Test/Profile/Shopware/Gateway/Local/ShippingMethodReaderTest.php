<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ShippingMethodReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class ShippingMethodReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var ShippingMethodReader
     */
    private $shippingMethodReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->shippingMethodReader = new ShippingMethodReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ShippingMethodDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->shippingMethodReader->supports($this->migrationContext));

        $data = $this->shippingMethodReader->read($this->migrationContext);

        static::assertCount(5, $data);
        static::assertSame('9', $data[0]['id']);
        static::assertSame('Standard Versand', $data[0]['name']);
        static::assertCount(1, $data[0]['shippingCosts']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('10', $data[1]['id']);
        static::assertSame('Versandkosten nach Gewicht', $data[1]['name']);
        static::assertCount(7, $data[1]['shippingCosts']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->shippingMethodReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->shippingMethodReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(5, $totalStruct->getTotal());
    }
}
