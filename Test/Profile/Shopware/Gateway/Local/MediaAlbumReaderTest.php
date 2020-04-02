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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\MediaAlbumReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class MediaAlbumReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var MediaAlbumReader
     */
    private $mediaAlbumReader;

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

        $this->mediaAlbumReader = new MediaAlbumReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new MediaFolderDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->mediaAlbumReader->supports($this->migrationContext));

        $data = $this->mediaAlbumReader->read($this->migrationContext);

        static::assertCount(13, $data);
        static::assertSame('-1', $data[9]['id']);
        static::assertSame('200x200;600x600;1280x1280;120x120', $data[9]['setting']['thumbnail_size']);
        static::assertSame('de_DE', $data[9]['_locale']);

        static::assertSame('-9', $data[7]['id']);
        static::assertSame('', $data[7]['setting']['thumbnail_size']);
        static::assertSame('de_DE', $data[7]['_locale']);
    }
}
