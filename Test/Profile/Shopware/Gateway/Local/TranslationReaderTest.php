<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TranslationReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class TranslationReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var TranslationReader
     */
    private $translationReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->translationReader = new TranslationReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new TranslationDataSet(),
            50,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->translationReader->supports($this->migrationContext));

        $data = $this->translationReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('199', $data[0]['id']);
        static::assertSame('article', $data[0]['objecttype']);
        static::assertSame('172', $data[0]['objectkey']);
        static::assertSame('2', $data[0]['objectlanguage']);
        static::assertSame('en-GB', $data[0]['locale']);

        static::assertSame('200', $data[1]['id']);
        static::assertSame('article', $data[1]['objecttype']);
        static::assertSame('170', $data[1]['objectkey']);
        static::assertSame('2', $data[1]['objectlanguage']);
        static::assertSame('en-GB', $data[1]['locale']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->translationReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->translationReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(126, $totalStruct->getTotal());
    }
}
