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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\BasicSettingsDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\MediaDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\NewsletterRecipientDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\NumberRangeDataSelection;
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
                'number_range' => new TotalStruct('number_range', 5),
                'newsletter_recipient' => new TotalStruct('newsletter_recipient', 50),
                'language' => new TotalStruct('language', 1),
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
            new BasicSettingsDataSelection(),
            new NewsletterRecipientDataSelection(),
            new NumberRangeDataSelection(),
        ]));
    }

    public function testGetDataSelections(): void
    {
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
          $this->connection
        );

        $expected = [
            0 => (new BasicSettingsDataSelection())->getData()->getId(),
            1 => (new NumberRangeDataSelection())->getData()->getId(),
            2 => (new ProductDataSelection())->getData()->getId(),
            3 => (new CustomerAndOrderDataSelection())->getData()->getId(),
            4 => (new MediaDataSelection())->getData()->getId(),
            5 => (new NewsletterRecipientDataSelection())->getData()->getId(),
        ];

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);
        static::assertCount(6, $dataSelections->getElements());

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
            new Shopware55Profile(),
            $this->connection
        );

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);

        static::assertCount(1, $dataSelections);
        static::assertSame($dataSelections->first()->getId(), (new MediaDataSelection())->getData()->getId());
    }

    public function testGetDataSelectionById(): void
    {
        $this->dataSelectionRegistry = new DataSelectionRegistry(new DummyCollection([new MediaDataSelection()]));
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection
        );

        $dataSelections = $this->dataSelectionRegistry->getDataSelectionsByIds($migrationContext, $this->environmentInformation, ['media']);

        static::assertCount(1, $dataSelections);
        static::assertInstanceOf(DataSelectionStruct::class, $dataSelections->get('media'));
    }
}
