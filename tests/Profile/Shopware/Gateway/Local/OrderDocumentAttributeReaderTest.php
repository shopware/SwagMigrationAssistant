<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderDocumentAttributeReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('fundamentals@after-sales')]
class OrderDocumentAttributeReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private OrderDocumentAttributeReader $orderDocumentAttributeReader;

    private MigrationContext $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->orderDocumentAttributeReader = new OrderDocumentAttributeReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            $this->connection,
            new Shopware55Profile(),
            null,
            new OrderDocumentAttributeDataSet(),
            $this->runId,
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->orderDocumentAttributeReader->supports($this->migrationContext));

        $data = $this->orderDocumentAttributeReader->read($this->migrationContext);

        static::assertCount(1, $data);
        static::assertSame('documentID', $data[0]['name']);
        static::assertSame('integer', $data[0]['type']);
        static::assertSame('de-DE', $data[0]['_locale']);
    }
}
