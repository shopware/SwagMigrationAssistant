<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\ConverterNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerGroupAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ManufacturerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NewsletterRecipientDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54MediaConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MediaConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56MediaConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57MediaConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;
use Symfony\Component\HttpFoundation\Response;

class ConverterRegistryTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    protected function setUp(): void
    {
        $this->converterRegistry = $this->getContainer()->get(ConverterRegistry::class);
    }

    /**
     * @dataProvider converterProvider
     *
     * @param class-string<object> $converterClass
     */
    public function testConverterCollection(ProfileInterface $profile, DataSet $dataSet, string $converterClass): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $migrationContext = new MigrationContext(
            $profile,
            $connection,
            Uuid::randomHex(),
            $dataSet,
            0,
            250
        );
        $result = $this->converterRegistry->getConverter($migrationContext);

        static::assertInstanceOf($converterClass, $result);
    }

    public function testGetConverterNotFound(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            Uuid::randomHex(),
            new FooDataSet(),
            0,
            250
        );

        try {
            $this->converterRegistry->getConverter($migrationContext);
        } catch (\Exception $e) {
            /* @var ConverterNotFoundException $e */
            static::assertInstanceOf(ConverterNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }

    public function converterProvider(): array
    {
        return [
            [
                new Shopware54Profile(),
                new CategoryAttributeDataSet(),
                Shopware54CategoryAttributeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new CategoryDataSet(),
                Shopware54CategoryConverter::class,
            ],
            [
                new Shopware54Profile(),
                new CurrencyDataSet(),
                Shopware54CurrencyConverter::class,
            ],
            [
                new Shopware54Profile(),
                new CustomerAttributeDataSet(),
                Shopware54CustomerAttributeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new CustomerDataSet(),
                Shopware54CustomerConverter::class,
            ],
            [
                new Shopware54Profile(),
                new CustomerGroupAttributeDataSet(),
                Shopware54CustomerGroupAttributeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new LanguageDataSet(),
                Shopware54LanguageConverter::class,
            ],
            [
                new Shopware54Profile(),
                new ManufacturerAttributeDataSet(),
                Shopware54ManufacturerAttributeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new MediaDataSet(),
                Shopware54MediaConverter::class,
            ],
            [
                new Shopware54Profile(),
                new MediaFolderDataSet(),
                Shopware54MediaFolderConverter::class,
            ],
            [
                new Shopware54Profile(),
                new NewsletterRecipientDataSet(),
                Shopware54NewsletterRecipientConverter::class,
            ],
            [
                new Shopware54Profile(),
                new NumberRangeDataSet(),
                Shopware54NumberRangeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new OrderAttributeDataSet(),
                Shopware54OrderAttributeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new OrderDataSet(),
                Shopware54OrderConverter::class,
            ],
            [
                new Shopware54Profile(),
                new OrderDocumentDataSet(),
                Shopware54OrderDocumentConverter::class,
            ],
            [
                new Shopware54Profile(),
                new ProductAttributeDataSet(),
                Shopware54ProductAttributeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new ProductDataSet(),
                Shopware54ProductConverter::class,
            ],
            [
                new Shopware54Profile(),
                new ProductPriceAttributeDataSet(),
                Shopware54ProductPriceAttributeConverter::class,
            ],
            [
                new Shopware54Profile(),
                new PropertyGroupOptionDataSet(),
                Shopware54PropertyGroupOptionConverter::class,
            ],
            [
                new Shopware54Profile(),
                new SalesChannelDataSet(),
                Shopware54SalesChannelConverter::class,
            ],
            [
                new Shopware54Profile(),
                new TranslationDataSet(),
                Shopware54TranslationConverter::class,
            ],
            [
                new Shopware55Profile(),
                new CategoryAttributeDataSet(),
                Shopware55CategoryAttributeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new CategoryDataSet(),
                Shopware55CategoryConverter::class,
            ],
            [
                new Shopware55Profile(),
                new CurrencyDataSet(),
                Shopware55CurrencyConverter::class,
            ],
            [
                new Shopware55Profile(),
                new CustomerAttributeDataSet(),
                Shopware55CustomerAttributeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new CustomerDataSet(),
                Shopware55CustomerConverter::class,
            ],
            [
                new Shopware55Profile(),
                new CustomerGroupAttributeDataSet(),
                Shopware55CustomerGroupAttributeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new LanguageDataSet(),
                Shopware55LanguageConverter::class,
            ],
            [
                new Shopware55Profile(),
                new ManufacturerAttributeDataSet(),
                Shopware55ManufacturerAttributeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new MediaDataSet(),
                Shopware55MediaConverter::class,
            ],
            [
                new Shopware55Profile(),
                new MediaFolderDataSet(),
                Shopware55MediaFolderConverter::class,
            ],
            [
                new Shopware55Profile(),
                new NewsletterRecipientDataSet(),
                Shopware55NewsletterRecipientConverter::class,
            ],
            [
                new Shopware55Profile(),
                new NumberRangeDataSet(),
                Shopware55NumberRangeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new OrderAttributeDataSet(),
                Shopware55OrderAttributeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new OrderDataSet(),
                Shopware55OrderConverter::class,
            ],
            [
                new Shopware55Profile(),
                new OrderDocumentDataSet(),
                Shopware55OrderDocumentConverter::class,
            ],
            [
                new Shopware55Profile(),
                new ProductAttributeDataSet(),
                Shopware55ProductAttributeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new ProductDataSet(),
                ProductConverter::class,
            ],
            [
                new Shopware55Profile(),
                new ProductPriceAttributeDataSet(),
                Shopware55ProductPriceAttributeConverter::class,
            ],
            [
                new Shopware55Profile(),
                new PropertyGroupOptionDataSet(),
                Shopware55PropertyGroupOptionConverter::class,
            ],
            [
                new Shopware55Profile(),
                new SalesChannelDataSet(),
                Shopware55SalesChannelConverter::class,
            ],
            [
                new Shopware55Profile(),
                new TranslationDataSet(),
                Shopware55TranslationConverter::class,
            ],
            [
                new Shopware56Profile(),
                new CategoryAttributeDataSet(),
                Shopware56CategoryAttributeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new CategoryDataSet(),
                Shopware56CategoryConverter::class,
            ],
            [
                new Shopware56Profile(),
                new CurrencyDataSet(),
                Shopware56CurrencyConverter::class,
            ],
            [
                new Shopware56Profile(),
                new CustomerAttributeDataSet(),
                Shopware56CustomerAttributeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new CustomerDataSet(),
                Shopware56CustomerConverter::class,
            ],
            [
                new Shopware56Profile(),
                new CustomerGroupAttributeDataSet(),
                Shopware56CustomerGroupAttributeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new LanguageDataSet(),
                Shopware56LanguageConverter::class,
            ],
            [
                new Shopware56Profile(),
                new ManufacturerAttributeDataSet(),
                Shopware56ManufacturerAttributeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new MediaDataSet(),
                Shopware56MediaConverter::class,
            ],
            [
                new Shopware56Profile(),
                new MediaFolderDataSet(),
                Shopware56MediaFolderConverter::class,
            ],
            [
                new Shopware56Profile(),
                new NewsletterRecipientDataSet(),
                Shopware56NewsletterRecipientConverter::class,
            ],
            [
                new Shopware56Profile(),
                new NumberRangeDataSet(),
                Shopware56NumberRangeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new OrderAttributeDataSet(),
                Shopware56OrderAttributeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new OrderDataSet(),
                Shopware56OrderConverter::class,
            ],
            [
                new Shopware56Profile(),
                new OrderDocumentDataSet(),
                Shopware56OrderDocumentConverter::class,
            ],
            [
                new Shopware56Profile(),
                new ProductAttributeDataSet(),
                Shopware56ProductAttributeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new ProductDataSet(),
                Shopware56ProductConverter::class,
            ],
            [
                new Shopware56Profile(),
                new ProductPriceAttributeDataSet(),
                Shopware56ProductPriceAttributeConverter::class,
            ],
            [
                new Shopware56Profile(),
                new PropertyGroupOptionDataSet(),
                Shopware56PropertyGroupOptionConverter::class,
            ],
            [
                new Shopware56Profile(),
                new SalesChannelDataSet(),
                Shopware56SalesChannelConverter::class,
            ],
            [
                new Shopware56Profile(),
                new TranslationDataSet(),
                Shopware56TranslationConverter::class,
            ],
            [
                new Shopware57Profile(),
                new CategoryAttributeDataSet(),
                Shopware57CategoryAttributeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new CategoryDataSet(),
                Shopware57CategoryConverter::class,
            ],
            [
                new Shopware57Profile(),
                new CurrencyDataSet(),
                Shopware57CurrencyConverter::class,
            ],
            [
                new Shopware57Profile(),
                new CustomerAttributeDataSet(),
                Shopware57CustomerAttributeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new CustomerDataSet(),
                Shopware57CustomerConverter::class,
            ],
            [
                new Shopware57Profile(),
                new CustomerGroupAttributeDataSet(),
                Shopware57CustomerGroupAttributeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new LanguageDataSet(),
                Shopware57LanguageConverter::class,
            ],
            [
                new Shopware57Profile(),
                new ManufacturerAttributeDataSet(),
                Shopware57ManufacturerAttributeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new MediaDataSet(),
                Shopware57MediaConverter::class,
            ],
            [
                new Shopware57Profile(),
                new MediaFolderDataSet(),
                Shopware57MediaFolderConverter::class,
            ],
            [
                new Shopware57Profile(),
                new NewsletterRecipientDataSet(),
                Shopware57NewsletterRecipientConverter::class,
            ],
            [
                new Shopware57Profile(),
                new NumberRangeDataSet(),
                Shopware57NumberRangeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new OrderAttributeDataSet(),
                Shopware57OrderAttributeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new OrderDataSet(),
                Shopware57OrderConverter::class,
            ],
            [
                new Shopware57Profile(),
                new OrderDocumentDataSet(),
                Shopware57OrderDocumentConverter::class,
            ],
            [
                new Shopware57Profile(),
                new ProductAttributeDataSet(),
                Shopware57ProductAttributeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new ProductDataSet(),
                Shopware57ProductConverter::class,
            ],
            [
                new Shopware57Profile(),
                new ProductPriceAttributeDataSet(),
                Shopware57ProductPriceAttributeConverter::class,
            ],
            [
                new Shopware57Profile(),
                new PropertyGroupOptionDataSet(),
                Shopware57PropertyGroupOptionConverter::class,
            ],
            [
                new Shopware57Profile(),
                new SalesChannelDataSet(),
                Shopware57SalesChannelConverter::class,
            ],
            [
                new Shopware57Profile(),
                new TranslationDataSet(),
                Shopware57TranslationConverter::class,
            ],
        ];
    }
}
