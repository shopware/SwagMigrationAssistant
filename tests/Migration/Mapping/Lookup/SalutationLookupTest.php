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
use Shopware\Core\System\Salutation\SalutationEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalutationLookup;

class SalutationLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $salutationKey, ?string $expectedResult): void
    {
        $salutationLookup = $this->getSalutationLookup();

        static::assertSame($expectedResult, $salutationLookup->get($salutationKey, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $salutationKey, ?string $expectedResult): void
    {
        $salutationLookup = $this->getMockedSalutationLookup();

        static::assertSame($expectedResult, $salutationLookup->get($salutationKey, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $salutationLookup = $this->getMockedSalutationLookup();

        $cacheProperty = new \ReflectionProperty(SalutationLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($salutationLookup));

        $salutationLookup->reset();

        static::assertEmpty($cacheProperty->getValue($salutationLookup));
    }

    /**
     * @return array<int, array{salutationKey: string|null, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['salutationKey' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['salutationKey' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{salutationKey: string|null, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $list = self::getContainer()->get('salutation.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $mailTemplateType) {
            static::assertInstanceOf(SalutationEntity::class, $mailTemplateType);
            $returnData[] = ['salutationKey' => $mailTemplateType->getSalutationKey(), 'expectedResult' => $mailTemplateType->getId()];
        }

        return $returnData;
    }

    private function getSalutationLookup(): SalutationLookup
    {
        return $this->getContainer()->get(SalutationLookup::class);
    }

    private function getMockedSalutationLookup(): SalutationLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('SalutationLookup repository should not be called'));

        $salutationLookup = new SalutationLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(SalutationLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['salutationKey']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($salutationLookup, $cache);

        return $salutationLookup;
    }
}
