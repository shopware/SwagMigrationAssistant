<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaThumbnailSizeLookup;

class MediaThumbnailSizeLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(int $width, int $height, ?string $expectedResult): void
    {
        $mediaThumbnailSizeLookup = $this->getMediaThumbnailSizeLookup();

        static::assertSame($expectedResult, $mediaThumbnailSizeLookup->get($width, $height, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(int $width, int $height, ?string $expectedResult): void
    {
        $mediaThumbnailSizeLookup = $this->getMockedMediaThumbnailSizeLookup();

        static::assertSame($expectedResult, $mediaThumbnailSizeLookup->get($width, $height, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $mediaThumbnailSizeLookup = $this->getMockedMediaThumbnailSizeLookup();

        $cacheProperty = new \ReflectionProperty(MediaThumbnailSizeLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($mediaThumbnailSizeLookup));

        $mediaThumbnailSizeLookup->reset();

        static::assertEmpty($cacheProperty->getValue($mediaThumbnailSizeLookup));
    }

    /**
     * @return array<int, array{width: int, height: int, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();

        $returnData[] = ['width' => 888, 'height' => 888, 'expectedResult' => null];
        $returnData[] = ['width' => 999, 'height' => 999, 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{width: int, height: int, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $mediaSizeRepository = self::getContainer()->get('media_thumbnail_size.repository');
        $mediaSizes = $mediaSizeRepository->search(new Criteria(), Context::createDefaultContext());
        $returnData = [];
        foreach ($mediaSizes->getEntities() as $mediaSize) {
            static::assertInstanceOf(MediaThumbnailSizeEntity::class, $mediaSize);

            $returnData[] = [
                'width' => $mediaSize->getWidth(),
                'height' => $mediaSize->getHeight(),
                'expectedResult' => $mediaSize->getId(),
            ];
        }

        return $returnData;
    }

    private function getMediaThumbnailSizeLookup(): MediaThumbnailSizeLookup
    {
        return new MediaThumbnailSizeLookup(
            $this->getContainer()->get('media_thumbnail_size.repository')
        );
    }

    private function getMockedMediaThumbnailSizeLookup(): MediaThumbnailSizeLookup
    {
        $mediaThumbnailSizeRepository = $this->createMock(EntityRepository::class);
        $mediaThumbnailSizeRepository->method('search')->willThrowException(
            new \Exception('MediaThumbnailSizeLookup repository should not be called')
        );
        $mediaThumbnailSizeLookup = new MediaThumbnailSizeLookup($mediaThumbnailSizeRepository);

        $reflectionProperty = new \ReflectionProperty(MediaThumbnailSizeLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[\sprintf('%s-%s', $data['width'], $data['height'])] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($mediaThumbnailSizeLookup, $cacheData);

        return $mediaThumbnailSizeLookup;
    }
}
