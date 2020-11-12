<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MainVariantRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\MainVariantRelationReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class MainVariantRelationReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var MainVariantRelationReader
     */
    private $reader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->reader = new MainVariantRelationReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new MainVariantRelationDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->reader->supports($this->migrationContext));

        $data = $this->reader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('5', $data[0]['id']);
        static::assertSame('SW10005.1', $data[0]['ordernumber']);

        static::assertSame('2', $data[1]['id']);
        static::assertSame('SW10002.3', $data[1]['ordernumber']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->reader->supportsTotal($this->migrationContext));

        $totalStruct = $this->reader->readTotal($this->migrationContext);

        $dataset = $this->migrationContext->getDataSet();
        static::assertNotNull($dataset);
        static::assertNotNull($totalStruct);
        static::assertSame($dataset::getEntity(), $totalStruct->getEntityName());
        static::assertSame(27, $totalStruct->getTotal());
    }
}
