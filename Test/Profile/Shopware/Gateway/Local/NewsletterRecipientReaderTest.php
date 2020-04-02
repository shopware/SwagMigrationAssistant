<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NewsletterRecipientDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\NewsletterRecipientReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class NewsletterRecipientReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var NewsletterRecipientReader
     */
    private $newsletterRecipientReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->newsletterRecipientReader = new NewsletterRecipientReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new NewsletterRecipientDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        static::assertTrue($this->newsletterRecipientReader->supports($this->migrationContext));

        $data = $this->newsletterRecipientReader->read($this->migrationContext);

        static::assertCount(0, $data);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->newsletterRecipientReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->newsletterRecipientReader->readTotal($this->migrationContext);

        static::assertSame($this->migrationContext->getDataSet()::getEntity(), $totalStruct->getEntityName());
        static::assertSame(0, $totalStruct->getTotal());
    }
}
