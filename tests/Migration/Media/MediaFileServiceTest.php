<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Media;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;

#[Package('services-settings')]
class MediaFileServiceTest extends TestCase
{
    /**
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $writtenData
     * @param array<string, mixed> $expected
     */
    #[DataProvider('filterUnwrittenData')]
    public function testFilterUnwrittenData(array $converted, array $writtenData, array $expected): void
    {
        $mediaFileService = $this->createMediaFileService();

        $result = $mediaFileService->filterUnwrittenData($converted, $writtenData);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function filterUnwrittenData(): array
    {
        return [
            'all data has no written information' => [
                'converted' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                    'uuid2' => ['id' => 'uuid2', 'name' => 'test2'],
                    'uuid3' => ['id' => 'uuid3', 'name' => 'test3'],
                ],
                'writtenData' => [],
                'expected' => [],
            ],

            'no information and unwritten is mixed' => [
                'converted' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                    'uuid2' => ['id' => 'uuid2', 'name' => 'test2'],
                    'uuid3' => ['id' => 'uuid3', 'name' => 'test3'],
                ],
                'writtenData' => [
                    'uuid1' => ['written' => false],
                    'uuid2' => ['written' => false],
                ],
                'expected' => [],
            ],

            'no information written and unwritten is mixed' => [
                'converted' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                    'uuid2' => ['id' => 'uuid2', 'name' => 'test2'],
                    'uuid3' => ['id' => 'uuid3', 'name' => 'test3'],
                ],
                'writtenData' => [
                    'uuid1' => ['written' => true],
                    'uuid2' => ['written' => false],
                ],
                'expected' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                ],
            ],

            'written and unwritten is mixed' => [
                'converted' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                    'uuid2' => ['id' => 'uuid2', 'name' => 'test2'],
                    'uuid3' => ['id' => 'uuid3', 'name' => 'test3'],
                ],
                'writtenData' => [
                    'uuid1' => ['written' => true],
                    'uuid2' => ['written' => false],
                    'uuid3' => ['written' => true],
                ],
                'expected' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                    'uuid3' => ['id' => 'uuid3', 'name' => 'test3'],
                ],
            ],

            'all is written' => [
                'converted' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                    'uuid2' => ['id' => 'uuid2', 'name' => 'test2'],
                    'uuid3' => ['id' => 'uuid3', 'name' => 'test3'],
                ],
                'writtenData' => [
                    'uuid1' => ['written' => true],
                    'uuid2' => ['written' => true],
                    'uuid3' => ['written' => true],
                ],
                'expected' => [
                    'uuid1' => ['id' => 'uuid1', 'name' => 'test1'],
                    'uuid2' => ['id' => 'uuid2', 'name' => 'test2'],
                    'uuid3' => ['id' => 'uuid3', 'name' => 'test3'],
                ],
            ],
        ];
    }

    /**
     * @param EntityRepository<SwagMigrationMediaFileCollection>|null $entityRepository
     */
    private function createMediaFileService(
        ?EntityRepository $entityRepository = null,
        ?EntityWriterInterface $entityWriter = null,
        ?EntityDefinition $mediaFileDefinition = null,
        ?ConverterRegistryInterface $converterRegistry = null,
    ): MediaFileService {
        return new MediaFileService(
            $entityRepository ?? $this->createMock(EntityRepository::class),
            $entityWriter ?? $this->createMock(EntityWriterInterface::class),
            $mediaFileDefinition ?? $this->createMock(EntityDefinition::class),
            $converterRegistry ?? $this->createMock(ConverterRegistryInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }
}
