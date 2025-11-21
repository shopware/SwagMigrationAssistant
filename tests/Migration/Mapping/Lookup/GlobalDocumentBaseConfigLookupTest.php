<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\Lookup\GlobalDocumentBaseConfigLookup;

class GlobalDocumentBaseConfigLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $documentTypeId, ?string $expectedResult): void
    {
        $globalDocumentBaseConfigLookup = $this->getGlobalDocumentBaseConfigLookup();

        static::assertSame($expectedResult, $globalDocumentBaseConfigLookup->get($documentTypeId, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $documentTypeId, ?string $expectedResult): void
    {
        $globalDocumentBaseConfigLookup = $this->getMockedGlobalDocumentBaseConfigLookup();

        static::assertSame($expectedResult, $globalDocumentBaseConfigLookup->get($documentTypeId, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $globalDocumentBaseConfigLookup = $this->getMockedGlobalDocumentBaseConfigLookup();

        $cacheProperty = new \ReflectionProperty(GlobalDocumentBaseConfigLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        $configCacheProperty = new \ReflectionProperty(GlobalDocumentBaseConfigLookup::class, 'configCache');
        $configCacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($globalDocumentBaseConfigLookup));
        static::assertNotEmpty($configCacheProperty->getValue($globalDocumentBaseConfigLookup));

        $globalDocumentBaseConfigLookup->reset();

        static::assertEmpty($cacheProperty->getValue($globalDocumentBaseConfigLookup));
        static::assertEmpty($configCacheProperty->getValue($globalDocumentBaseConfigLookup));
    }

    public function testGetBaseConfig(): void
    {
        $context = Context::createDefaultContext();

        $data = self::getDatabaseData();
        $documentTypeID = $data[0]['documentTypeId'];
        static::assertIsString($documentTypeID);

        $globalDocumentBaseConfigLookup = $this->getGlobalDocumentBaseConfigLookup();
        $configId = $globalDocumentBaseConfigLookup->get($documentTypeID, $context);
        static::assertIsString($configId);

        $baseConfig = $globalDocumentBaseConfigLookup->getBaseConfig($configId, $context);

        static::assertIsArray($baseConfig);
        static::assertArrayHasKey('fileTypes', $baseConfig);
        static::assertArrayHasKey('referencedDocumentType', $baseConfig);
        static::assertSame('invoice', $baseConfig['referencedDocumentType']);
    }

    public function testGetBaseConfigFromCache(): void
    {
        $context = Context::createDefaultContext();

        $globalDocumentBaseConfigLookup = $this->getMockedGlobalDocumentBaseConfigLookup();
        $baseConfigResult = $globalDocumentBaseConfigLookup->getBaseConfig('anyConfigId', $context);

        static::assertIsArray($baseConfigResult);
        static::assertSame($this->getCachedConfig(), $baseConfigResult);
    }

    /**
     * @return array<int, array{documentTypeId: string|null, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['documentTypeId' => Uuid::randomHex(), 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{documentTypeId: string|null, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('documentType');
        $criteria->addFilter(new EqualsFilter('global', true));

        $list = self::getContainer()->get('document_base_config.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $documentBaseConfig) {
            static::assertInstanceOf(DocumentBaseConfigEntity::class, $documentBaseConfig);
            $returnData[] = ['documentTypeId' => $documentBaseConfig->getDocumentType()?->getId(), 'expectedResult' => $documentBaseConfig->getId()];
        }

        return $returnData;
    }

    private function getGlobalDocumentBaseConfigLookup(): GlobalDocumentBaseConfigLookup
    {
        return $this->getContainer()->get(GlobalDocumentBaseConfigLookup::class);
    }

    private function getMockedGlobalDocumentBaseConfigLookup(): GlobalDocumentBaseConfigLookup
    {
        $documentBaseConfigRepository = $this->createMock(EntityRepository::class);
        $documentBaseConfigRepository->method('searchIds')->willThrowException(new \Exception('GlobalDocumentBaseConfigLookup repository should not be called'));

        $globalDocumentBaseConfigLookup = new GlobalDocumentBaseConfigLookup($documentBaseConfigRepository);

        $reflectionProperty = new \ReflectionProperty(GlobalDocumentBaseConfigLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['documentTypeId']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($globalDocumentBaseConfigLookup, $cache);

        $configCache = ['anyConfigId' => $this->getCachedConfig()];
        $reflectionProperty = new \ReflectionProperty(GlobalDocumentBaseConfigLookup::class, 'configCache');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($globalDocumentBaseConfigLookup, $configCache);

        return $globalDocumentBaseConfigLookup;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCachedConfig(): array
    {
        return [
            'vatId' => 'anyVatId',
            'bankBic' => 'anyBankBic',
            'bankIban' => 'anyBankIban',
            'bankName' => 'anyBankName',
            'pageSize' => 'a4',
            'fileTypes' => [
                0 => 'html',
                1 => 'pdf',
            ],
            'taxNumber' => 'anyTaxNumber',
            'taxOffice' => 'anyTaxOffice',
            'companyName' => 'Example Company',
            'itemsPerPage' => 10,
            'displayFooter' => true,
            'displayHeader' => true,
            'displayPrices' => true,
            'companyAddress' => 'anyAddress',
            'pageOrientation' => 'portrait',
            'displayLineItems' => true,
            'displayPageCount' => true,
            'executiveDirector' => 'anyExecutiveDirector',
            'placeOfFulfillment' => 'anyPlaceOfFulfillment',
            'placeOfJurisdiction' => 'anyPlaceOfJurisdiction',
            'displayReturnAddress' => true,
            'displayCompanyAddress' => true,
            'diplayLineItemPosition' => true,
            'referencedDocumentType' => 'anyReferencedDocumentType',
            'displayAdditionalNoteDelivery' => false,
        ];
    }
}
