<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SeoUrlTemplateLookup;

class SeoUrlTemplateLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(
        ?string $salesChannelId,
        string $routeName,
        ?string $expectedResult,
    ): void {
        $seoUrlTemplateLookup = $this->getSeoUrlTemplateLookup();

        static::assertSame($expectedResult, $seoUrlTemplateLookup->get($salesChannelId, $routeName, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(
        ?string $salesChannelId,
        string $routeName,
        string $expectedResult,
    ): void {
        $seoUrlTemplateLookup = $this->getMockedSeoUrlTemplateLookup();

        static::assertSame($expectedResult, $seoUrlTemplateLookup->get($salesChannelId, $routeName, Context::createDefaultContext()));
    }

    /**
     * @return array<int, array{salesChannelId: string|null, routeName: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();

        $returnData[] = ['salesChannelId' => null, 'routeName' => 'foo', 'expectedResult' => null];
        $returnData[] = ['salesChannelId' => Uuid::randomHex(), 'routeName' => 'foo', 'expectedResult' => null];
        $returnData[] = ['salesChannelId' => null, 'routeName' => 'bar', 'expectedResult' => null];
        $returnData[] = ['salesChannelId' => Uuid::randomHex(), 'routeName' => 'bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{salesChannelId: string|null, routeName: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $seoUrlTemplateRepository = static::getContainer()->get('seo_url_template.repository');
        $list = $seoUrlTemplateRepository->search(new Criteria(), Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $seoUrlTemplate) {
            static::assertInstanceOf(SeoUrlTemplateEntity::class, $seoUrlTemplate);

            $returnData[] = [
                'salesChannelId' => $seoUrlTemplate->getSalesChannelId(),
                'routeName' => $seoUrlTemplate->getRouteName(),
                'expectedResult' => $seoUrlTemplate->getId(),
            ];
        }

        return $returnData;
    }

    private function getSeoUrlTemplateLookup(): SeoUrlTemplateLookup
    {
        $seoUrlTemplateLookup = $this->getContainer()->get(SeoUrlTemplateLookup::class);
        static::assertInstanceOf(SeoUrlTemplateLookup::class, $seoUrlTemplateLookup);

        return $seoUrlTemplateLookup;
    }

    private function getMockedSeoUrlTemplateLookup(): SeoUrlTemplateLookup
    {
        $seoUrlTemplateRepository = $this->createMock(EntityRepository::class);
        $seoUrlTemplateRepository->method('search')->willThrowException(
            new \Exception('SeoUrlTemplate repository should not be called')
        );

        $seoUrlTemplateLookup = new SeoUrlTemplateLookup($seoUrlTemplateRepository);

        $reflectionProperty = new \ReflectionProperty(SeoUrlTemplateLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[\sprintf('%s-%s', $data['salesChannelId'], $data['routeName'])] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($seoUrlTemplateLookup, $cacheData);

        return $seoUrlTemplateLookup;
    }
}
