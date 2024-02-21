<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\AbstractReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\MediaReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('services-settings')]
class MediaReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private AbstractReader $mediaReader;

    private MigrationContext $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->mediaReader = new MediaReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new MediaDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->mediaReader->supports($this->migrationContext));

        $data = $this->mediaReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('665', $data[0]['id']);
        static::assertSame('-12', $data[0]['albumID']);
        static::assertSame('media/image/accessoires.png', $data[0]['path']);
        static::assertSame('png', $data[0]['extension']);
        static::assertSame('de-DE', $data[0]['_locale']);

        static::assertSame('666', $data[1]['id']);
        static::assertSame('-12', $data[1]['albumID']);
        static::assertSame('media/image/beachdreams.png', $data[1]['path']);
        static::assertSame('png', $data[1]['extension']);
        static::assertSame('de-DE', $data[1]['_locale']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->mediaReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->mediaReader->readTotal($this->migrationContext);
        static::assertInstanceOf(TotalStruct::class, $totalStruct);

        $dataset = $this->migrationContext->getDataSet();
        static::assertInstanceOf(DataSet::class, $dataset);
        static::assertSame($dataset::getEntity(), $totalStruct->getEntityName());
        static::assertSame(591, $totalStruct->getTotal());
    }
}
