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
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DeliveryTimeLookup;

class DeliveryTimeLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(int $minValue, int $maxValue, string $unit, ?string $expectedResult): void
    {
        $deliveryTimeLookup = $this->getDeliveryTimeLookup();

        $result = $deliveryTimeLookup->get($minValue, $maxValue, $unit, Context::createDefaultContext());

        static::assertSame($expectedResult, $result);
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(int $minValue, int $maxValue, string $unit, ?string $expectedResult): void
    {
        $deliveryTimeLookup = $this->getMockedDeliveryTimeLookup();

        $result = $deliveryTimeLookup->get($minValue, $maxValue, $unit, Context::createDefaultContext());

        static::assertSame($expectedResult, $result);
    }

    public function testReset(): void
    {
        $deliveryTimeLookup = $this->getMockedDeliveryTimeLookup();

        $cacheProperty = new \ReflectionProperty(DeliveryTimeLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($deliveryTimeLookup));

        $deliveryTimeLookup->reset();

        static::assertEmpty($cacheProperty->getValue($deliveryTimeLookup));
    }

    /**
     * @return array<int, array{min: int, max: int, unit: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();

        $returnData[] = [
            'min' => 0,
            'max' => 1,
            'unit' => 'Foo-Unit',
            'expectedResult' => null,
        ];

        $returnData[] = [
            'min' => 2,
            'max' => 3,
            'unit' => 'Bar-Unit',
            'expectedResult' => null,
        ];

        $returnData[] = [
            'min' => 4,
            'max' => 5,
            'unit' => 'Baz-Unit',
            'expectedResult' => null,
        ];

        return $returnData;
    }

    /**
     * @return list<array{min: int, max: int, unit: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $deliveryTimeRepository = self::getContainer()->get('delivery_time.repository');
        $list = $deliveryTimeRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $deliveryTime) {
            static::assertInstanceOf(DeliveryTimeEntity::class, $deliveryTime);

            $returnData[] = [
                'min' => $deliveryTime->getMin(),
                'max' => $deliveryTime->getMax(),
                'unit' => $deliveryTime->getUnit(),
                'expectedResult' => $deliveryTime->getId(),
            ];
        }

        return $returnData;
    }

    private function getDeliveryTimeLookup(): DeliveryTimeLookup
    {
        return new DeliveryTimeLookup(
            $this->getContainer()->get('delivery_time.repository')
        );
    }

    private function getMockedDeliveryTimeLookup(): DeliveryTimeLookup
    {
        $deliveryTimeRepo = $this->createMock(EntityRepository::class);
        $deliveryTimeRepo->method('searchIds')->willThrowException(
            new \Exception('DeliveryTime repository should not be called')
        );
        $deliveryTimeLookup = new DeliveryTimeLookup($deliveryTimeRepo);

        $reflectionProperty = new \ReflectionProperty(DeliveryTimeLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[\sprintf('%d-%d-%s', $data['min'], $data['max'], $data['unit'])] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($deliveryTimeLookup, $cacheData);

        return $deliveryTimeLookup;
    }
}
