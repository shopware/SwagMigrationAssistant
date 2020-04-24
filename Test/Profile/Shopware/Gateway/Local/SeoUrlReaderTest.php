<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SeoUrlDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\SeoUrlReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class SeoUrlReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var SeoUrlReader
     */
    private $seoUrlReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->seoUrlReader = new SeoUrlReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new SeoUrlDataSet(),
            50,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->seoUrlReader->supports($this->migrationContext));

        $data = $this->seoUrlReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('1226', $data[0]['id']);
        static::assertSame('Genusswelten-EN/', $data[0]['path']);
        static::assertSame('0', $data[0]['main']);
        static::assertSame('2', $data[0]['subshopID']);
        static::assertSame('en-GB', $data[0]['_locale']);
        static::assertSame('cat', $data[0]['type']);
        static::assertSame('43', $data[0]['typeId']);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new SeoUrlDataSet(),
            200,
            10
        );
        $data = $this->seoUrlReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('153', $data[0]['id']);
        static::assertSame('Sommerwelten/162/Sommer-Sandale-Pink', $data[0]['path']);
        static::assertSame('1', $data[0]['main']);
        static::assertSame('1', $data[0]['subshopID']);
        static::assertSame('de-DE', $data[0]['_locale']);
        static::assertSame('detail', $data[0]['type']);
        static::assertSame('162', $data[0]['typeId']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->seoUrlReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->seoUrlReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(495, $totalStruct->getTotal());
    }
}
