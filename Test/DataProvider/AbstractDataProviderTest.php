<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\DataProvider;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationCollection;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationEntity;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\Tax\TaxCollection;
use Shopware\Core\System\Tax\TaxEntity;
use SwagMigrationAssistant\Test\Mock\DataProvider\DummyDataProvider;

class AbstractDataProviderTest extends TestCase
{
    /**
     * @var DummyDataProvider
     */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->dataProvider = new DummyDataProvider();
    }

    public function testCleanupProvidedData(): void
    {
        $expectedCleanResultData = [
            [
                'taxRate' => 7.0,
                'name' => 'Reduced rate',
                'id' => '0526c8c027f4431a92407fb2fa16752c',
            ],
            [
                'taxRate' => 9.0,
                'name' => 'MyCustomTax',
                'id' => 'a2f2918aaf5d42a2ba219c82c2bc275d',
            ],
        ];

        $tax1 = new TaxEntity();
        $tax1->setId($expectedCleanResultData[0]['id']);
        $tax1->setName($expectedCleanResultData[0]['name']);
        $tax1->setTaxRate($expectedCleanResultData[0]['taxRate']);
        $tax1->setExtensions(['foreignKeys' => new ArrayStruct()]);
        $tax1->setCreatedAt(new \DateTimeImmutable());

        $tax2 = new TaxEntity();
        $tax2->setId($expectedCleanResultData[1]['id']);
        $tax2->setName($expectedCleanResultData[1]['name']);
        $tax2->setTaxRate($expectedCleanResultData[1]['taxRate']);
        $tax2->setExtensions(['foreignKeys' => new ArrayStruct()]);
        $tax2->setCreatedAt(new \DateTimeImmutable());
        $tax2->setUpdatedAt(new \DateTimeImmutable());

        $rawResultData = new TaxCollection([$tax1, $tax2]);

        $cleanResultData = $this->dataProvider->cleanupProvidedData($rawResultData);

        static::assertSame($expectedCleanResultData, $cleanResultData);
    }

    public function testCleanupProvidedDataWithWriteProtectedFields(): void
    {
        $expectedCleanResultData = [
            [
                'name' => 'My store',
                'level' => 1,
                'active' => true,
                'displayNestedProducts' => true,
                'translations' => [
                    [
                        'categoryId' => '3756f93ae4bc4a73a10f9b00e514ad67',
                        'name' => 'Mein Shop',
                        'languageId' => '1d4d1c257dab4759ae9dbc15314822a5',
                    ],
                    [
                        'categoryId' => '3756f93ae4bc4a73a10f9b00e514ad67',
                        'name' => 'My store',
                        'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
                    ],
                ],
                'cmsPageId' => '772242e71c2a4c319572d03f733a1574',
                'type' => 'folder',
                'id' => '3756f93ae4bc4a73a10f9b00e514ad67',
            ],
        ];

        $category1 = new CategoryEntity();
        $category1->setId($expectedCleanResultData[0]['id']);
        $category1->setUniqueIdentifier($expectedCleanResultData[0]['id']);
        $category1->setName($expectedCleanResultData[0]['name']);
        $category1->setBreadcrumb([
            $expectedCleanResultData[0]['id'] => $expectedCleanResultData[0]['name'],
        ]);
        $category1->setLevel(1);
        $category1->setActive(true);
        $category1->setChildCount(3);
        $category1->setDisplayNestedProducts(true);
        $category1->setCmsPageId($expectedCleanResultData[0]['cmsPageId']);
        $category1->setType($expectedCleanResultData[0]['type']);

        $category1Translation1 = new CategoryTranslationEntity();
        $category1Translation1->setCategoryId($expectedCleanResultData[0]['id']);
        $category1Translation1->setLanguageId($expectedCleanResultData[0]['translations'][0]['languageId']);
        $category1Translation1->setUniqueIdentifier($expectedCleanResultData[0]['id'] . $expectedCleanResultData[0]['translations'][0]['languageId']);
        $category1Translation1->setName($expectedCleanResultData[0]['translations'][0]['name']);
        $category1Translation1->setBreadcrumb([
            $expectedCleanResultData[0]['id'] => $expectedCleanResultData[0]['translations'][0]['name'],
        ]);
        $category1Translation1->setExtensions(['foreignKeys' => new ArrayStruct()]);
        $category1Translation1->setCreatedAt(new \DateTimeImmutable());
        $category1Translation1->setUpdatedAt(new \DateTimeImmutable());

        $category1Translation2 = new CategoryTranslationEntity();
        $category1Translation2->setCategoryId($expectedCleanResultData[0]['id']);
        $category1Translation2->setLanguageId($expectedCleanResultData[0]['translations'][1]['languageId']);
        $category1Translation2->setUniqueIdentifier($expectedCleanResultData[0]['id'] . $expectedCleanResultData[0]['translations'][1]['languageId']);
        $category1Translation2->setName($expectedCleanResultData[0]['translations'][1]['name']);
        $category1Translation2->setBreadcrumb([
            $expectedCleanResultData[0]['id'] => $expectedCleanResultData[0]['translations'][1]['name'],
        ]);
        $category1Translation2->setExtensions(['foreignKeys' => new ArrayStruct()]);
        $category1Translation2->setCreatedAt(new \DateTimeImmutable());
        $category1Translation2->setUpdatedAt(new \DateTimeImmutable());

        $category1->setTranslations(new CategoryTranslationCollection([$category1Translation1, $category1Translation2]));
        $category1->setTranslated([
            'breadcrumb' => [
                $expectedCleanResultData[0]['id'] => $expectedCleanResultData[0]['name'],
            ],
            'name' => $expectedCleanResultData[0]['name'],
            'customFields' => [],
            'slotConfig' => null,
            'externalLink' => null,
            'description' => null,
            'metaTitle' => null,
            'metaDescription' => null,
            'keywords' => null,
        ]);
        $category1->setExtensions(['foreignKeys' => new ArrayStruct()]);
        $category1->setCreatedAt(new \DateTimeImmutable());
        $category1->setUpdatedAt(new \DateTimeImmutable());

        $rawResultData = new CategoryCollection([$category1]);

        $cleanResultData = $this->dataProvider->cleanupProvidedData($rawResultData, [
            // remove write protected fields
            'afterCategoryId',
            'childCount',
            'breadcrumb',
            'autoIncrement',
            'visibleChildCount',
        ]);

        static::assertSame($expectedCleanResultData, $cleanResultData);
    }
}
