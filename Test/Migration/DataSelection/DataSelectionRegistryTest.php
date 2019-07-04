<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Migration\DataSelection;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\MediaDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\DummyCollection;

class DataSelectionRegistryTest extends TestCase
{
    /**
     * @var DataSelectionRegistry
     */
    private $dataSelectionRegistry;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var EnvironmentInformation
     */
    private $environmentInformation;

    protected function setUp(): void
    {
        $this->environmentInformation = new EnvironmentInformation(
            '',
            '',
            '',
            [
                'product' => new TotalStruct('product', 100),
                'customer' => new TotalStruct('customer', 5),
                'media' => new TotalStruct('media', 100),
            ],
            []
        );
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setCredentialFields([]);

        $this->dataSelectionRegistry = new DataSelectionRegistry(new DummyCollection([
            new MediaDataSelection(),
            new ProductDataSelection(),
            new CustomerAndOrderDataSelection(),
        ]));
    }

    public function testGetDataSelections(): void
    {
        $migrationContext = new MigrationContext(
          $this->connection
        );
        $profile = new Shopware55Profile();
        $migrationContext->setProfile($profile);

        $expected = [
            0 => (new ProductDataSelection())->getData()->getId(),
            1 => (new CustomerAndOrderDataSelection())->getData()->getId(),
            2 => (new MediaDataSelection())->getData()->getId(),
        ];

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);
        static::assertCount(3, $dataSelections->getElements());

        $i = 0;
        /** @var DataSelectionStruct $selection */
        foreach ($dataSelections->getIterator() as $selection) {
            static::assertSame($expected[$i], $selection->getId());
            ++$i;
        }
    }

    public function testGetDataSelectionsWithOnlyOneDataSelection(): void
    {
        $this->dataSelectionRegistry = new DataSelectionRegistry(new DummyCollection([new MediaDataSelection()]));
        $migrationContext = new MigrationContext(
            $this->connection
        );
        $profile = new Shopware55Profile();
        $migrationContext->setProfile($profile);

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);

        static::assertCount(1, $dataSelections);
        static::assertSame($dataSelections->first()->getId(), (new MediaDataSelection())->getData()->getId());
    }
}
