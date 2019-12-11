<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\DataSelection;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Profile\Dummy\DummyProfile;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;

class DataSetRegistryTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var string
     */
    private $runId;

    protected function setUp(): void
    {
        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setCredentialFields([]);
        $this->dataSetRegistry = $this->getContainer()->get(DataSetRegistry::class);
    }

    public function testSupports(): void
    {
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ProductDataSet(),
            0,
            250
        );
        $dataSets = $this->dataSetRegistry->getDataSets($migrationContext);

        static::assertNotEmpty($dataSets);
        static::assertInstanceOf(DataSet::class, $dataSets[5]);
    }

    public function testDataSetNotFound(): void
    {
        $migrationContext = new MigrationContext(
            new DummyProfile(),
            $this->connection,
            $this->runId,
            new FooDataSet(),
            0,
            250
        );
        $dataSets = $this->dataSetRegistry->getDataSets($migrationContext);
        static::assertEmpty($dataSets);

        static::expectException(DataSetNotFoundException::class);
        $this->dataSetRegistry->getDataSet($migrationContext, 'foo');
    }
}
