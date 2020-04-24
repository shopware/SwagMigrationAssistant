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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderAttributeReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class OrderAttributeReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var OrderAttributeReader
     */
    private $orderAttributeReader;

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

        $this->orderAttributeReader = new OrderAttributeReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new OrderAttributeDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->orderAttributeReader->supports($this->migrationContext));

        $data = $this->orderAttributeReader->read($this->migrationContext);

        static::assertCount(6, $data);
        static::assertSame('attribute1', $data[0]['name']);
        static::assertSame('text', $data[0]['type']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('attribute2', $data[1]['name']);
        static::assertSame('text', $data[1]['type']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }
}
