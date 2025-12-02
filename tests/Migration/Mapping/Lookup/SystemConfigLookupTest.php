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
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemConfigLookup;

class SystemConfigLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $configurationKey, ?string $salesChannelId, ?string $expectedResult): void
    {
        $systemConfigLookup = $this->getSystemConfigLookup();

        static::assertSame($expectedResult, $systemConfigLookup->get($configurationKey, $salesChannelId, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $configurationKey, ?string $salesChannelId, ?string $expectedResult): void
    {
        $systemConfigLookup = $this->getMockedSystemConfigLookup();

        static::assertSame($expectedResult, $systemConfigLookup->get($configurationKey, $salesChannelId, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $systemConfigLookup = $this->getMockedSystemConfigLookup();

        $cacheProperty = new \ReflectionProperty(SystemConfigLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($systemConfigLookup));

        $systemConfigLookup->reset();

        static::assertEmpty($cacheProperty->getValue($systemConfigLookup));
    }

    /**
     * @return array<int, array{configurationKey: string, salesChannelId: string|null, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['configurationKey' => 'Foo', 'salesChannelId' => Uuid::randomHex(), 'expectedResult' => null];
        $returnData[] = ['configurationKey' => 'Bar', 'salesChannelId' => null, 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{configurationKey: string, salesChannelId: string|null, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $list = self::getContainer()->get('system_config.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $systemConfig) {
            static::assertInstanceOf(SystemConfigEntity::class, $systemConfig);
            $returnData[] = [
                'configurationKey' => $systemConfig->getConfigurationKey(),
                'salesChannelId' => $systemConfig->getSalesChannelId(),
                'expectedResult' => $systemConfig->getId()];
        }

        return $returnData;
    }

    private function getSystemConfigLookup(): SystemConfigLookup
    {
        return static::getContainer()->get(SystemConfigLookup::class);
    }

    private function getMockedSystemConfigLookup(): SystemConfigLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('SystemConfigLookup repository should not be called'));

        $systemConfigLookup = new SystemConfigLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(SystemConfigLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['configurationKey'] . '-' . $data['salesChannelId']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($systemConfigLookup, $cache);

        return $systemConfigLookup;
    }
}
