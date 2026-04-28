<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\LanguageReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('fundamentals@after-sales')]
class LanguageReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private LanguageReader $languageReader;

    private MigrationContext $migrationContext;

    private Connection $dbConnection;

    private int $customerLocaleId = 9999;

    private bool $customerLocaleInserted = false;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $connectionFactory = new ConnectionFactory();
        $this->languageReader = new LanguageReader($connectionFactory);

        $this->migrationContext = new MigrationContext(
            $this->connection,
            new Shopware55Profile(),
            null,
            new LanguageDataSet(),
            $this->runId,
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());

        $this->dbConnection = $connectionFactory->createDatabaseConnection($this->migrationContext);
        $this->dbConnection->executeStatement('UPDATE s_user SET language = :language WHERE id = :id', [
            'language' => 1,
            'id' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // reset customer to the fixture default locale
        $this->dbConnection->executeStatement('UPDATE s_user SET language = :language WHERE id = :id', [
            'language' => 1,
            'id' => 1,
        ]);

        if ($this->customerLocaleInserted) {
            $this->dbConnection->executeStatement('DELETE FROM s_core_locales WHERE id = :id', ['id' => $this->customerLocaleId]);
        }
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

    public function testReadIncludesCustomerLocalesOutsideShopLocales(): void
    {
        $existingLocaleId = $this->dbConnection->fetchOne(
            'SELECT id FROM s_core_locales WHERE locale = :locale',
            ['locale' => 'ar_EG']
        );

        if ($existingLocaleId === false) {
            $this->dbConnection->executeStatement(
                'INSERT INTO s_core_locales (id, locale, language, territory) VALUES (:id, :locale, :language, :territory)',
                [
                    'id' => $this->customerLocaleId,
                    'locale' => 'ar_EG',
                    'language' => 'Arabic',
                    'territory' => 'Egypt',
                ]
            );

            $this->customerLocaleInserted = true;
        } else {
            $this->customerLocaleId = (int) $existingLocaleId;
        }

        $this->dbConnection->executeStatement('UPDATE s_user SET language = :language WHERE id = :id', [
            'language' => $this->customerLocaleId,
            'id' => 1,
        ]);

        $data = $this->languageReader->read($this->migrationContext);

        static::assertCount(3, $data);
        static::assertContains('ar-EG', \array_column($data, 'locale'));
    }
}
