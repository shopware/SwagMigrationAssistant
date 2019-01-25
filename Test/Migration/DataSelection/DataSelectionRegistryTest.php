<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\DataSelection;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\DataSelection\AssetDataSelection;
use SwagMigrationNext\Profile\Shopware55\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationNext\Profile\Shopware55\DataSelection\ProductCategoryTranslationDataSelection;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;

class DataSelectionRegistryTest extends TestCase
{
    /**
     * @var DataSelectionRegistry
     */
    private $dataSelectionRegistry;

    protected function setUp(): void
    {
        $this->dataSelectionRegistry = new DataSelectionRegistry(new DummyCollection([
            new AssetDataSelection(),
            new ProductCategoryTranslationDataSelection(),
            new CustomerAndOrderDataSelection(),
        ]));
    }

    public function testGetDataSelections(): void
    {
        $migrationContext = new MigrationContext(
          '',
          '',
          Shopware55Profile::PROFILE_NAME,
          Shopware55ApiGateway::GATEWAY_TYPE,
          '',
            0,
            0
        );

        $expected = [
            0 => (new ProductCategoryTranslationDataSelection())->getData()->getId(),
            1 => (new CustomerAndOrderDataSelection())->getData()->getId(),
            2 => (new AssetDataSelection())->getData()->getId(),
        ];

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext);
        self::assertCount(3, $dataSelections->getElements());

        $i = 0;
        /** @var DataSelectionStruct $selection */
        foreach ($dataSelections->getIterator() as $selection) {
            self::assertSame($expected[$i], $selection->getId());
            ++$i;
        }
    }

    public function testGetDataSelectionsWithOnlyOneDataSelection(): void
    {
        $this->dataSelectionRegistry = new DataSelectionRegistry(new DummyCollection([new AssetDataSelection()]));
        $migrationContext = new MigrationContext(
            '',
            '',
            Shopware55Profile::PROFILE_NAME,
            Shopware55ApiGateway::GATEWAY_TYPE,
            '',
            0,
            0
        );

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext);

        self::assertCount(1, $dataSelections);
        self::assertSame($dataSelections->first()->getId(), (new AssetDataSelection())->getData()->getId());
    }
}
