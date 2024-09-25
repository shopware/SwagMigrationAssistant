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
use SwagMigrationAssistant\Migration\Mapping\Lookup\DocumentTypeLookup;

class DocumentTypeLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $technicalName, ?string $expectedResult): void
    {
        $documentTypeLookup = $this->getDocumentTypeLookup();

        static::assertSame($expectedResult, $documentTypeLookup->get($technicalName, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $technicalName, ?string $expectedResult): void
    {
        $documentTypeLookup = $this->getMockedDocumentTypeLookup();

        static::assertSame($expectedResult, $documentTypeLookup->get($technicalName, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $documentTypeLookup = $this->getMockedDocumentTypeLookup();

        $cacheProperty = new \ReflectionProperty(DocumentTypeLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($documentTypeLookup));

        $documentTypeLookup->reset();

        static::assertEmpty($cacheProperty->getValue($documentTypeLookup));
    }

    /**
     * @return array<int, array{technicalName: string, expectedResult: ?string}>
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
        $documentTypeRepository = self::getContainer()->get('document_type.repository');
        $list = $documentTypeRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $returnData = [];
        foreach ($list as $documentType) {
            $returnData[] = [
                'technicalName' => $documentType->getTechnicalName(),
                'expectedResult' => $documentType->getId(),
            ];
        }

        return $returnData;
    }

    private function getDocumentTypeLookup(): DocumentTypeLookup
    {
        return new DocumentTypeLookup(
            $this->getContainer()->get('document_type.repository')
        );
    }

    private function getMockedDocumentTypeLookup(): DocumentTypeLookup
    {
        $documentTypeRepository = $this->createMock(EntityRepository::class);
        $documentTypeRepository->method('search')->willThrowException(
            new \Exception('DocumentTypeLookup repository should not be called')
        );
        $documentTypeLookup = new DocumentTypeLookup($documentTypeRepository);

        $reflectionProperty = new \ReflectionProperty(DocumentTypeLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();
        $cacheData = [];
        foreach ($databaseData as $data) {
            $cacheData[$data['technicalName']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($documentTypeLookup, $cacheData);

        return $documentTypeLookup;
    }
}
