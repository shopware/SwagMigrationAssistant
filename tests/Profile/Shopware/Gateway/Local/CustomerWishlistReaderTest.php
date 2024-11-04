<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerWishlistDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerWishlistReader;
use SwagMigrationAssistant\Test\LocalConnectionTestCase;

#[Package('services-settings')]
class CustomerWishlistReaderTest extends LocalConnectionTestCase
{
    private CustomerWishlistReader $customerWishlistReader;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getExternalConnection();
        $this->customerWishlistReader = new CustomerWishlistReader($this->getConnectionFactory());
    }

    public function testRead(): void
    {
        $expectedData = [
            'sUniqueID' => 'unique-id-1',
            'userID' => '1',
            'articlename' => 'Kommode Shabby Chic',
            'articleID' => '68',
            'ordernumber' => 'SW10067',
            'datum' => '2024-10-17 13:54:26',
            'subshopID' => '1',
        ];

        $migrationContext = $this->getMigrationContext();
        static::assertTrue($this->customerWishlistReader->supports($migrationContext));

        $sql = \file_get_contents(__DIR__ . '/_fixtures/order_notes.sql');
        static::assertIsString($sql);

        $this->connection->executeStatement($sql);

        $wishlist = $this->customerWishlistReader->read($migrationContext);
        static::assertCount(5, $wishlist);

        $product = $wishlist[0];
        static::assertArrayHasKey('id', $product);
        unset($product['id']);
        static::assertSame($expectedData, $product);
    }

    public function testReadReturnsLimitedBatchSize(): void
    {
        $this->setLimitAndOffset(2, 0);
        $migrationContext = $this->getMigrationContext();

        $sql = \file_get_contents(__DIR__ . '/_fixtures/order_notes.sql');
        static::assertIsString($sql);

        $this->connection->executeStatement($sql);

        $wishlist = $this->customerWishlistReader->read($migrationContext);
        static::assertCount(2, $wishlist);
    }

    public function testFetchWillReturnOnlyValidOrderNotes(): void
    {
        $migrationContext = $this->getMigrationContext();

        $sql = \file_get_contents(__DIR__ . '/_fixtures/order_notes_with_invalid_data.sql');
        static::assertIsString($sql);

        $this->connection->executeStatement($sql);

        $wishlistItems = $this->connection->executeQuery('SELECT `sUniqueID` FROM s_order_notes')->fetchAllAssociative();
        static::assertCount(3, $wishlistItems);
        static::assertSame('unique-id-invalid-wishlist-item', $wishlistItems[2]['sUniqueID']);

        $wishlist = $this->customerWishlistReader->read($migrationContext);
        static::assertCount(2, $wishlist);

        foreach ($wishlist as $wishListItem) {
            static::assertNotSame('unique-id-invalid-wishlist-item', $wishListItem['sUniqueID']);
        }
    }

    public function testReadTotal(): void
    {
        $migrationContext = $this->getMigrationContext();

        $sql = \file_get_contents(__DIR__ . '/_fixtures/order_notes.sql');
        static::assertIsString($sql);

        $this->connection->executeStatement($sql);

        $wishlistStruct = $this->customerWishlistReader->readTotal($migrationContext);
        static::assertInstanceOf(TotalStruct::class, $wishlistStruct);
        static::assertSame(5, $wishlistStruct->getTotal());
        static::assertSame('customer_wishlist', $wishlistStruct->getEntityName());
    }

    protected function getDataSet(): DataSet
    {
        return new CustomerWishlistDataSet();
    }
}
