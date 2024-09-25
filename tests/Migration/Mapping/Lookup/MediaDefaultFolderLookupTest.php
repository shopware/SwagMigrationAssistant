<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaDefaultFolderLookup;

class MediaDefaultFolderLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $entityName, ?string $expectedResult): void
    {
        $mediaFolderLookup = $this->getMediaDefaultFolderLookup();

        static::assertSame($expectedResult, $mediaFolderLookup->get($entityName, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $entityName, ?string $expectedResult): void
    {
        $mediaFolderLookup = $this->getMockedMediaDefaultFolderLookup();

        static::assertSame($expectedResult, $mediaFolderLookup->get($entityName, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $mediaFolderLookup = $this->getMockedMediaDefaultFolderLookup();

        $cacheProperty = new \ReflectionProperty(MediaDefaultFolderLookup::class, 'cache');
        $cacheProperty->setAccessible(true);
        static::assertNotEmpty($cacheProperty->getValue($mediaFolderLookup));

        $mediaFolderLookup->reset();

        static::assertEmpty($cacheProperty->getValue($mediaFolderLookup));
    }

    /**
     * @return array<int, array{entityName: string, expectedResult: ?string}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['entityName' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['entityName' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{entityName: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $mediaFolderRepository = self::getContainer()->get('media_default_folder.repository');
        $list = $mediaFolderRepository->search(new Criteria(), Context::createDefaultContext());

        $returnData = [];
        foreach ($list->getEntities() as $mediaFolder) {
            $returnData[] = [
                'entityName' => $mediaFolder->getEntity(),
                'expectedResult' => $mediaFolder->getFolder()->getId(),
            ];
        }

        return $returnData;
    }

    private function getMediaDefaultFolderLookup(): MediaDefaultFolderLookup
    {
        return new MediaDefaultFolderLookup(
            $this->getContainer()->get('media_default_folder.repository')
        );
    }

    private function getMockedMediaDefaultFolderLookup(): MediaDefaultFolderLookup
    {
        $mediaDefaultFolderRepository = $this->createMock(EntityRepository::class);
        $mediaDefaultFolderRepository->method('search')->willThrowException(
            new \Exception('MediaDefaultFolderLookup repository should not be called')
        );
        $documentTypeLookup = new MediaDefaultFolderLookup($mediaDefaultFolderRepository);

        $reflectionProperty = new \ReflectionProperty(MediaDefaultFolderLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['entityName']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($documentTypeLookup, $cacheData);

        return $documentTypeLookup;
    }
}
