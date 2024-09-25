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
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;

class LocaleLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $localeCode, ?string $expectedResult): void
    {
        $localeLookup = $this->getLocaleLookup();

        static::assertSame($expectedResult, $localeLookup->get($localeCode, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $localeCode, ?string $expectedResult): void
    {
        $localeLookup = $this->getMockedLocaleLookup();

        static::assertSame($expectedResult, $localeLookup->get($localeCode, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $localeLookup = $this->getMockedLocaleLookup();

        $cacheProperty = new \ReflectionProperty(LocaleLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($localeLookup));

        $localeLookup->reset();

        static::assertEmpty($cacheProperty->getValue($localeLookup));
    }

    /**
     * @return array<int, array{localeCode: string, expectedResult: ?string}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();

        $returnData[] = ['localeCode' => 'FOO', 'expectedResult' => null];
        $returnData[] = ['localeCode' => 'BAR', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{localeCode: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $localeRepository = self::getContainer()->get('locale.repository');
        $list = $localeRepository->search(new Criteria(), Context::createDefaultContext());

        $returnData = [];
        foreach ($list->getEntities() as $locale) {
            $returnData[] = [
                'localeCode' => $locale->getCode(),
                'expectedResult' => $locale->getId()
            ];
        }

        return $returnData;
    }

    private function getLocaleLookup(): LocaleLookup
    {
        return new LocaleLookup(
            $this->getContainer()->get('locale.repository')
        );
    }

    private function getMockedLocaleLookup(): LocaleLookup
    {
        $localeRepository = $this->createMock(EntityRepository::class);
        $localeRepository->method('search')->willThrowException(
            new \Exception('LocaleLookup repository should not be called')
        );

        $cacheData = [];
        foreach (self::getDatabaseData() as $data) {
            $cacheData[$data['localeCode']] = $data['expectedResult'];
        }

        $localeLookup = new LocaleLookup($localeRepository);

        $cacheProperty = new \ReflectionProperty(LocaleLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue($localeLookup, $cacheData);

        return $localeLookup;
    }
}
