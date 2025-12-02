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
use Shopware\Core\System\NumberRange\Aggregate\NumberRangeType\NumberRangeTypeEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeTypeLookup;

class NumberRangeTypeLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $technicalName, ?string $expectedResult): void
    {
        $globalDocumentBaseConfigLookup = $this->getNumberRangeTypeLookup();

        static::assertSame($expectedResult, $globalDocumentBaseConfigLookup->get($technicalName, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $technicalName, ?string $expectedResult): void
    {
        $globalDocumentBaseConfigLookup = $this->getMockedNumberRangeTypeLookup();

        static::assertSame($expectedResult, $globalDocumentBaseConfigLookup->get($technicalName, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $globalDocumentBaseConfigLookup = $this->getMockedNumberRangeTypeLookup();

        $cacheProperty = new \ReflectionProperty(NumberRangeTypeLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($globalDocumentBaseConfigLookup));

        $globalDocumentBaseConfigLookup->reset();

        static::assertEmpty($cacheProperty->getValue($globalDocumentBaseConfigLookup));
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
        $list = self::getContainer()->get('number_range_type.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $numberRangeType) {
            static::assertInstanceOf(NumberRangeTypeEntity::class, $numberRangeType);
            $returnData[] = ['technicalName' => $numberRangeType->getTechnicalName(), 'expectedResult' => $numberRangeType->getId()];
        }

        return $returnData;
    }

    private function getNumberRangeTypeLookup(): NumberRangeTypeLookup
    {
        return static::getContainer()->get(NumberRangeTypeLookup::class);
    }

    private function getMockedNumberRangeTypeLookup(): NumberRangeTypeLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('NumberRangeTypeLookup repository should not be called'));

        $numberRangeTypeLookup = new NumberRangeTypeLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(NumberRangeTypeLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['technicalName']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($numberRangeTypeLookup, $cache);

        return $numberRangeTypeLookup;
    }
}
