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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('fundamentals@after-sales')]
class CustomerReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private const CUSTOMER_LANGUAGE_SHOP_ID = 32;

    private CustomerReader $customerReader;

    private MigrationContext $migrationContext;

    private Connection $dbConnection;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $connectionFactory = new ConnectionFactory();
        $this->customerReader = new CustomerReader($connectionFactory);

        $this->migrationContext = new MigrationContext(
            $this->connection,
            new Shopware55Profile(),
            null,
            new CustomerDataSet(),
            $this->runId,
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());

        $this->dbConnection = $connectionFactory->createDatabaseConnection($this->migrationContext);

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
        static::assertTrue($this->customerReader->supports($this->migrationContext));

        $data = $this->customerReader->read($this->migrationContext);

        static::assertCount(3, $data);
        static::assertSame('1', $data[0]['id']);
        static::assertSame('md5', $data[0]['encoder']);
        static::assertSame('1', $data[0]['default_billing_address_id']);
        static::assertSame('3', $data[0]['default_shipping_address_id']);
        static::assertSame('prepayment', $data[0]['defaultpayment']['name']);
        static::assertSame('en-GB', $data[0]['customerlanguage']['locale']);
        static::assertSame('0', $data[0]['shop']['customer_scope']);
        static::assertCount(2, $data[0]['addresses']);

        static::assertSame('2', $data[1]['id']);
        static::assertSame('md5', $data[1]['encoder']);
        static::assertSame('2', $data[1]['default_billing_address_id']);
        static::assertSame('4', $data[1]['default_shipping_address_id']);
        static::assertSame('invoice', $data[1]['defaultpayment']['name']);
        static::assertSame('de-DE', $data[1]['customerlanguage']['locale']);
        static::assertSame('0', $data[1]['shop']['customer_scope']);
        static::assertCount(2, $data[1]['addresses']);

        static::assertSame('3', $data[2]['id']);
        static::assertSame('bcrypt', $data[2]['encoder']);
        static::assertSame('5', $data[2]['default_billing_address_id']);
        static::assertSame('5', $data[2]['default_shipping_address_id']);
        static::assertSame('prepayment', $data[2]['defaultpayment']['name']);
        static::assertSame('de-DE', $data[2]['customerlanguage']['locale']);
        static::assertSame('0', $data[2]['shop']['customer_scope']);
        static::assertCount(1, $data[2]['addresses']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->customerReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->customerReader->readTotal($this->migrationContext);
        static::assertNotNull($totalStruct);

        $dataset = $this->migrationContext->getDataSet();
        static::assertNotNull($dataset);
        static::assertSame($dataset::getEntity(), $totalStruct->getEntityName());
        static::assertSame(3, $totalStruct->getTotal());
    }
}
