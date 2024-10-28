<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\Tax\Aggregate\TaxRuleType\TaxRuleTypeEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleTypeLookup;

class TaxRuleTypeLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $technicalName, ?string $expectedResult): void
    {
        $taxRuleTypeLookup = $this->getTaxRuleTypeLookup();

        static::assertSame($expectedResult, $taxRuleTypeLookup->get($technicalName, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $technicalName, ?string $expectedResult): void
    {
        $taxRuleTypeLookup = $this->getMockedTaxRuleTypeLookup();

        static::assertSame($expectedResult, $taxRuleTypeLookup->get($technicalName, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $taxRuleTypeLookup = $this->getMockedTaxRuleTypeLookup();

        $cacheProperty = new \ReflectionProperty(TaxRuleTypeLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($taxRuleTypeLookup));

        $taxRuleTypeLookup->reset();

        static::assertEmpty($cacheProperty->getValue($taxRuleTypeLookup));
    }

    /**
     * @return array<int, array{technicalName: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['technicalName' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['technicalName' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{technicalName: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $list = self::getContainer()->get('tax_rule_type.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $taxRuleType) {
            static::assertInstanceOf(TaxRuleTypeEntity::class, $taxRuleType);
            $returnData[] = ['technicalName' => $taxRuleType->getTechnicalName(), 'expectedResult' => $taxRuleType->getId()];
        }

        return $returnData;
    }

    private function getTaxRuleTypeLookup(): TaxRuleTypeLookup
    {
        return $this->getContainer()->get(TaxRuleTypeLookup::class);
    }

    private function getMockedTaxRuleTypeLookup(): TaxRuleTypeLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('TaxRuleTypeLookup repository should not be called'));

        $taxRuleTypeLookup = new TaxRuleTypeLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(TaxRuleTypeLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['technicalName']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($taxRuleTypeLookup, $cache);

        return $taxRuleTypeLookup;
    }
}
