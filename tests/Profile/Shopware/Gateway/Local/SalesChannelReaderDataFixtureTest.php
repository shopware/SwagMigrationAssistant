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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\SalesChannelReader;
use SwagMigrationAssistant\Test\LocalConnectionTestCase;

#[Package('fundamentals@after-sales')]
class SalesChannelReaderDataFixtureTest extends LocalConnectionTestCase
{
    private SalesChannelReader $salesChannelReader;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getExternalConnection();
        $this->salesChannelReader = new SalesChannelReader($this->getConnectionFactory());
    }

    public function testReadReturnsLimitedBatchSize(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/subshops.sql');
        static::assertIsString($sql);

        $this->connection->executeStatement($sql);
        $totalStruct = $this->salesChannelReader->readTotal($this->getMigrationContext());
        static::assertInstanceOf(TotalStruct::class, $totalStruct);
        static::assertGreaterThan(3, $totalStruct->getTotal());

        $this->setLimitAndOffset(3, 0);
        $data = $this->salesChannelReader->read($this->getMigrationContext());
        static::assertCount(3, $data);
    }

    protected function getDataSet(): DataSet
    {
        return new SalesChannelDataSet();
    }
}
