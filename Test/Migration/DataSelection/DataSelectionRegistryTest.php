<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductReviewDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\SeoUrlDataSelection;
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
                'media' => new TotalStruct('media', 1200),
                'number_range' => new TotalStruct('number_range', 5),
                'newsletter_recipient' => new TotalStruct('newsletter_recipient', 50),
                'language' => new TotalStruct('language', 1),
                'category' => new TotalStruct('category', 23),
                'order' => new TotalStruct('order', 23),
                'category_custom_field' => new TotalStruct('category_custom_field', 34),
                'customer_group_custom_field' => new TotalStruct('customer_group_custom_field', 76),
                'currency' => new TotalStruct('currency', 1),
                'customer_group' => new TotalStruct('customer_group', 12),
                'sales_channel' => new TotalStruct('sales_channel', 7),
                'seo_url' => new TotalStruct('seo_url', 4000),
                'product_review' => new TotalStruct('product_review', 3000),
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
            new ProductReviewDataSelection(),
            new SeoUrlDataSelection(),
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
            1 => (new ProductDataSelection())->getData()->getId(),
            2 => (new CustomerAndOrderDataSelection())->getData()->getId(),
            3 => (new SeoUrlDataSelection())->getData()->getId(),
            4 => (new ProductReviewDataSelection())->getData()->getId(),
            5 => (new MediaDataSelection())->getData()->getId(),
            6 => (new NewsletterRecipientDataSelection())->getData()->getId(),
        ];

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);
        static::assertCount(7, $dataSelections->getElements());

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
        static::assertNotNull($dataSelections->first());
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

    public function testEntityNamesRequiredForCount(): void
    {
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection
        );

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);
        $totals = $this->environmentInformation->getTotals();

        /** @var DataSelectionStruct $dataSelection */
        foreach ($dataSelections as $dataSelection) {
            static::assertInstanceOf(DataSelectionStruct::class, $dataSelection);

            if (\count($dataSelection->getEntityNamesRequiredForCount()) > 0) {
                static::assertNotEmpty($dataSelection->getCountedTotal($totals));
            }
        }
    }

    public function testEntityNamesRequiredForCountValues(): void
    {
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection
        );

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);

        /** @var DataSelectionStruct $dataSelection */
        foreach ($dataSelections as $dataSelection) {
            switch ($dataSelection->getId()) {
                case 'basicSettings':
                    static::assertSame($dataSelection->getTotal(), 159);

                    break;
                case 'numberRanges':
                    static::assertSame($dataSelection->getTotal(), 5);

                    break;
                case 'products':
                    static::assertSame($dataSelection->getTotal(), 100);

                    break;
                case 'customersOrders':
                    static::assertSame($dataSelection->getTotal(), 28);

                    break;
                case 'media':
                    static::assertSame($dataSelection->getTotal(), 1200);

                    break;
                case 'newsletterRecipient':
                    static::assertSame($dataSelection->getTotal(), 50);

                    break;
                case 'productReviews':
                    static::assertSame($dataSelection->getTotal(), 3000);

                    break;
                case 'seoUrls':
                    static::assertSame($dataSelection->getTotal(), 4000);

                    break;
            }
        }
    }
}
