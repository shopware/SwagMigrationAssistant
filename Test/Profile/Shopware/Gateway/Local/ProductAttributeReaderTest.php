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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductAttributeReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('services-settings')]
class ProductAttributeReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private ProductAttributeReader $productAttributeReader;

    private MigrationContext $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->productAttributeReader = new ProductAttributeReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ProductAttributeDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->productAttributeReader->supports($this->migrationContext));

        $data = $this->productAttributeReader->read($this->migrationContext);

        static::assertCount(20, $data);
        static::assertSame('attr1', $data[0]['name']);
        static::assertSame('text', $data[0]['type']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('attr2', $data[1]['name']);
        static::assertSame('text', $data[1]['type']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }
}
