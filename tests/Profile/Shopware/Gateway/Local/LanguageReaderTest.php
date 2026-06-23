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

    private const CUSTOMER_LANGUAGE_SHOP_ID = 32;

    private LanguageReader $languageReader;

    private MigrationContext $migrationContext;

    private Connection $dbConnection;

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

        // Insert a new shop with a different locale to test the language reader
        $this->dbConnection->executeStatement(
            'INSERT INTO s_core_shops (
                id, name, position, hosts, secure, locale_id, customer_scope, `default`, active
            ) VALUES (
                :id, :name, :position, :hosts, :secure, :localeId, :customerScope, :default, :active
            )',
            [
                'id' => self::CUSTOMER_LANGUAGE_SHOP_ID,
                'name' => 'English shop',
                'position' => 0,
                'hosts' => '',
                'secure' => 0,
                'localeId' => 2,
                'customerScope' => 0,
                'default' => 0,
                'active' => 1,
            ]
        );

        $this->dbConnection->executeStatement('UPDATE s_user SET language = :language WHERE id = :id', [
            'language' => self::CUSTOMER_LANGUAGE_SHOP_ID,
            'id' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Reset the language of the customer to the default shop's locale
        $this->dbConnection->executeStatement('UPDATE s_user SET language = :language WHERE id = :id', [
            'language' => 1,
            'id' => 1,
        ]);

        $this->dbConnection->executeStatement('DELETE FROM s_core_shops WHERE id = :id', [
            'id' => self::CUSTOMER_LANGUAGE_SHOP_ID,
        ]);
    }

    public function testRead(): void
    {
        static::assertTrue($this->languageReader->supports($this->migrationContext));

        $data = $this->languageReader->read($this->migrationContext);
        $locales = \array_column($data, 'locale');

        static::assertCount(2, $data);
        static::assertContains('de-DE', $locales);
        static::assertContains('en-GB', $locales);
        static::assertNotContains('bn-IN', $locales);

        foreach ($data as $language) {
            static::assertSame('de-DE', $language['_locale']);
            static::assertSame('de_DE', $language['translations'][0]['locale']);
            static::assertSame('en_GB', $language['translations'][1]['locale']);
        }
    }
}
