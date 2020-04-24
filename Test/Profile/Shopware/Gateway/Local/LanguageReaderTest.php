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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\LanguageReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class LanguageReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var LanguageReader
     */
    private $languageReader;

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

        $this->languageReader = new LanguageReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new LanguageDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->languageReader->supports($this->migrationContext));

        $data = $this->languageReader->read($this->migrationContext);

        static::assertCount(2, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertSame('de-DE', $data[0]['locale']);
        static::assertSame('de-DE', $data[0]['_locale']);
        static::assertSame('de_DE', $data[0]['translations'][0]['locale']);
        static::assertSame('en_GB', $data[0]['translations'][1]['locale']);

        static::assertSame('2', $data[1]['id']);
        static::assertSame('en-GB', $data[1]['locale']);
        static::assertSame('de-DE', $data[1]['_locale']);
        static::assertSame('de_DE', $data[1]['translations'][0]['locale']);
        static::assertSame('en_GB', $data[1]['translations'][1]['locale']);
    }
}
