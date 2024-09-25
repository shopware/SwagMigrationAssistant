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
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeLookup;

class NumberRangeLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $type, ?string $expectedResult): void
    {
        $numberRangeLookup = $this->getNumberRangeLookup();

        static::assertSame($expectedResult, $numberRangeLookup->get($type, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $type, ?string $expectedResult): void
    {
        $numberRangeLookup = $this->getMockedNumberRangeLookup();

        static::assertSame($expectedResult, $numberRangeLookup->get($type, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $numberRangeLookup = $this->getMockedNumberRangeLookup();

        $cacheProperty = new \ReflectionProperty(NumberRangeLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($numberRangeLookup));

        $numberRangeLookup->reset();

        static::assertEmpty($cacheProperty->getValue($numberRangeLookup));
    }

    /**
     * @return array<int, array{type: string, expectedResult: ?string}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['type' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['type' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{type: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('type');

        $numberRangeRepository = self::getContainer()->get('number_range.repository');
        $list = $numberRangeRepository->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list->getEntities() as $numberRange) {
            $returnData[] = [
                'type' => $numberRange->getType()->getTechnicalName(),
                'expectedResult' => $numberRange->getId(),
            ];
        }

        return $returnData;
    }

    private function getNumberRangeLookup(): NumberRangeLookup
    {
        $numberRangeLookup = $this->getContainer()->get(NumberRangeLookup::class);
        static::assertInstanceOf(NumberRangeLookup::class, $numberRangeLookup);

        return $numberRangeLookup;
    }

    private function getMockedNumberRangeLookup(): NumberRangeLookup
    {
        $numberRangeRepository = $this->createMock(EntityRepository::class);
        $numberRangeRepository->method('search')->willThrowException(
            new \Exception('NumberRangeLookup repository should not be called')
        );
        $mediaThumbnailSizeLookup = new NumberRangeLookup($numberRangeRepository);

        $reflectionProperty = new \ReflectionProperty(NumberRangeLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['type']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($mediaThumbnailSizeLookup, $cacheData);

        return $mediaThumbnailSizeLookup;
    }
}
