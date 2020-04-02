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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CategoryReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class CategoryReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var CategoryReader
     */
    private $categoryReader;

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

        $this->categoryReader = new CategoryReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CategoryDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->categoryReader->supports($this->migrationContext));

        $data = $this->categoryReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('3', $data[0]['id']);
        static::assertNull($data[0]['parent']);
        static::assertNull($data[0]['previousSiblingId']);

        static::assertSame('39', $data[1]['id']);
        static::assertNull($data[1]['parent']);
        static::assertSame('3', $data[1]['previousSiblingId']);

        static::assertSame('5', $data[2]['id']);
        static::assertSame('3', $data[2]['parent']);
        static::assertNull($data[2]['previousSiblingId']);

        static::assertSame('9', $data[3]['id']);
        static::assertSame('3', $data[3]['parent']);
        static::assertSame('5', $data[3]['previousSiblingId']);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CategoryDataSet(),
            10,
            10
        );

        $data = $this->categoryReader->read($this->migrationContext);

        static::assertCount(10, $data);

        static::assertSame('15', $data[0]['id']);
        static::assertSame('5', $data[0]['parent']);
        static::assertSame('14', $data[0]['previousSiblingId']);

        static::assertSame('34', $data[1]['id']);
        static::assertSame('6', $data[1]['parent']);
        static::assertNull($data[1]['previousSiblingId']);

        static::assertSame('35', $data[2]['id']);
        static::assertSame('6', $data[2]['parent']);
        static::assertSame('34', $data[2]['previousSiblingId']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->categoryReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->categoryReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(60, $totalStruct->getTotal());
    }
}
