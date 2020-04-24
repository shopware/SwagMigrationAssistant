<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class TableReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var TableReader
     */
    private $tableReader;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->tableReader = new TableReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ShippingMethodDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        $data = $this->tableReader->read($this->migrationContext, 's_core_config_elements', ['name' => 'shopsalutations']);

        static::assertCount(1, $data);
        static::assertSame('1016', $data[0]['id']);
        static::assertSame('shopsalutations', $data[0]['name']);
    }
}
