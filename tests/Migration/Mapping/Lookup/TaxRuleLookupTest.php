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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleLookup;

class TaxRuleLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $taxId, string $countryId, string $taxRuleTypeId, ?string $expectedResult): void
    {
        $taxRuleLookup = $this->getTaxRuleLookup();

        static::assertSame($expectedResult, $taxRuleLookup->get($taxId, $countryId, $taxRuleTypeId, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $taxId, string $countryId, string $taxRuleTypeId, ?string $expectedResult): void
    {
        $taxRuleLookup = $this->getMockedTaxRuleLookup();

        static::assertSame($expectedResult, $taxRuleLookup->get($taxId, $countryId, $taxRuleTypeId, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $taxRuleLookup = $this->getMockedTaxRuleLookup();

        $cacheProperty = new \ReflectionProperty(TaxRuleLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($taxRuleLookup));

        $taxRuleLookup->reset();

        static::assertEmpty($cacheProperty->getValue($taxRuleLookup));
    }

    /**
     * @return array<int, array{taxId: string, countryId: string, taxRuleTypeId: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = [
            'taxId' => Uuid::randomHex(),
            'countryId' => Uuid::randomHex(),
            'taxRuleTypeId' => Uuid::randomHex(),
            'expectedResult' => null,
        ];
        $returnData[] = [
            'taxId' => Uuid::randomHex(),
            'countryId' => Uuid::randomHex(),
            'taxRuleTypeId' => Uuid::randomHex(),
            'expectedResult' => null,
        ];

        return $returnData;
    }

    /**
     * @return array<int, array{taxId: string, countryId: string, taxRuleTypeId: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $list = self::getContainer()->get('tax_rule.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $taxRule) {
            static::assertInstanceOf(TaxRuleEntity::class, $taxRule);
            $returnData[] = [
                'taxId' => $taxRule->getTaxId(),
                'countryId' => $taxRule->getCountryId(),
                'taxRuleTypeId' => $taxRule->getTaxRuleTypeId(),
                'expectedResult' => $taxRule->getId(),
            ];
        }

        return $returnData;
    }

    private function getTaxRuleLookup(): TaxRuleLookup
    {
        return static::getContainer()->get(TaxRuleLookup::class);
    }

    private function getMockedTaxRuleLookup(): TaxRuleLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('TaxRuleLookup repository should not be called'));

        $taxRuleLookup = new TaxRuleLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(TaxRuleLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[\sprintf('%s-%s-%s', $data['taxId'], $data['countryId'], $data['taxRuleTypeId'])] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($taxRuleLookup, $cache);

        return $taxRuleLookup;
    }
}
