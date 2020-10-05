<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\PropertyGroupOptionReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class PropertyGroupOptionReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var PropertyGroupOptionReader
     */
    private $propertyGroupOptionReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->propertyGroupOptionReader = new PropertyGroupOptionReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new PropertyGroupOptionDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->propertyGroupOptionReader->supports($this->migrationContext));

        $data = $this->propertyGroupOptionReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('property', $data[0]['type']);
        static::assertSame('39', $data[0]['id']);
        static::assertSame('< 20%', $data[0]['name']);
        static::assertSame('1', $data[0]['group']['id']);
        static::assertSame('Alkoholgehalt', $data[0]['group']['name']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('property', $data[1]['type']);
        static::assertSame('40', $data[1]['id']);
        static::assertSame('>30%', $data[1]['name']);
        static::assertSame('1', $data[1]['group']['id']);
        static::assertSame('Alkoholgehalt', $data[1]['group']['name']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->propertyGroupOptionReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->propertyGroupOptionReader->readTotal($this->migrationContext);
        static::assertNotNull($totalStruct);
        $dataSet = $this->migrationContext->getDataSet();
        static::assertNotNull($dataSet);

        static::assertSame($dataSet::getEntity(), $totalStruct->getEntityName());
        static::assertSame(94, $totalStruct->getTotal());
    }
}
