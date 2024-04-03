<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Media\Process;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;

#[Package('services-settings')]
class HttpDownloadServiceBaseTest extends TestCase
{
    private string $runId;

    private MigrationContext $migrationContext;

    private Context $context;

    private DummyLoggingService $loggingService;

    protected function setUp(): void
    {
        $this->runId = Uuid::randomHex();
        $this->loggingService = new DummyLoggingService();
        $this->migrationContext = new MigrationContext(
            new Shopware6MajorProfile('6.6.0'),
            null,
            Uuid::randomHex(),
            null,
            0,
            100
        );
        $this->context = Context::createDefaultContext();
    }

    public function testSupports(): void
    {
        $httpDownloadServiceBase = $this->createBase([]);
        static::assertTrue($httpDownloadServiceBase->supports($this->migrationContext));
    }

    public function testProcessSucceeds(): void
    {
        $mediaFiles = [
            [
                'mediaId' => Uuid::randomHex(),
                'fileName' => 'test.jpg',
                'fileContent' => 'hello world',
                'uri' => 'http://test.localhost/test.jpg?random=123456789',
            ],
        ];
        $httpDownloadServiceBase = $this->createBase($mediaFiles);
        $initialWorkload = [
            new MediaProcessWorkloadStruct(
                $mediaFiles[0]['mediaId'],
                $this->runId,
            ),
        ];
        $resultWorkload = $httpDownloadServiceBase->process($this->migrationContext, $this->context, $initialWorkload, 1000 * 1000);

        static::assertEquals([
            new MediaProcessWorkloadStruct(
                $mediaFiles[0]['mediaId'],
                $this->runId,
                MediaProcessWorkloadStruct::FINISH_STATE,
                [
                    'file_size' => 11,
                    'uri' => 'http://test.localhost/test.jpg?random=123456789',
                ],
                0,
                11,
            ),
        ], $resultWorkload);

        static::assertEquals([], $this->loggingService->getLoggingArray());
    }

    public function testProcessWithRequestFailure(): void
    {
        $mediaFiles = [
            [
                'mediaId' => Uuid::randomHex(),
                'fileName' => 'test.jpg',
                'fileContent' => null,
                'uri' => 'http://test.localhost/test.jpg?random=123456789',
            ],
        ];
        $httpDownloadServiceBase = $this->createBase($mediaFiles);
        $initialWorkload = [
            new MediaProcessWorkloadStruct(
                $mediaFiles[0]['mediaId'],
                $this->runId,
            ),
        ];

        $resultWorkload = $httpDownloadServiceBase->process($this->migrationContext, $this->context, $initialWorkload, 1000 * 1000);
        static::assertEquals([
            new MediaProcessWorkloadStruct(
                $mediaFiles[0]['mediaId'],
                $this->runId,
                MediaProcessWorkloadStruct::FINISH_STATE,
                [
                    'file_size' => 0,
                    'uri' => 'http://test.localhost/test.jpg?random=123456789',
                ],
                1,
                0,
            ),
        ], $resultWorkload);
        static::assertEquals([], $this->loggingService->getLoggingArray());

        // second attempt
        $resultWorkload = $httpDownloadServiceBase->process($this->migrationContext, $this->context, $resultWorkload, 1000 * 1000);
        static::assertEquals([
            new MediaProcessWorkloadStruct(
                $mediaFiles[0]['mediaId'],
                $this->runId,
                MediaProcessWorkloadStruct::FINISH_STATE,
                [
                    'file_size' => 0,
                    'uri' => 'http://test.localhost/test.jpg?random=123456789',
                ],
                2,
                0,
            ),
        ], $resultWorkload);
        static::assertEquals([], $this->loggingService->getLoggingArray());

        // third attempt
        $resultWorkload = $httpDownloadServiceBase->process($this->migrationContext, $this->context, $resultWorkload, 1000 * 1000);
        static::assertEquals([
            new MediaProcessWorkloadStruct(
                $mediaFiles[0]['mediaId'],
                $this->runId,
                MediaProcessWorkloadStruct::FINISH_STATE,
                [
                    'file_size' => 0,
                    'uri' => 'http://test.localhost/test.jpg?random=123456789',
                ],
                3,
                0,
            ),
        ], $resultWorkload);
        static::assertEquals([], $this->loggingService->getLoggingArray());

        // fourth attempt
        $resultWorkload = $httpDownloadServiceBase->process($this->migrationContext, $this->context, $resultWorkload, 1000 * 1000);
        static::assertEquals([
            new MediaProcessWorkloadStruct(
                $mediaFiles[0]['mediaId'],
                $this->runId,
                MediaProcessWorkloadStruct::ERROR_STATE,
                [
                    'file_size' => 0,
                    'uri' => 'http://test.localhost/test.jpg?random=123456789',
                ],
                4,
                0,
            ),
        ], $resultWorkload);
        static::assertEquals([
            [
                'level' => 'warning',
                'code' => 'SWAG_MIGRATION_CANNOT_GET_MEDIA_FILE',
                'title' => 'The media file cannot be downloaded / copied',
                'description' => 'The media file with the uri "' . $mediaFiles[0]['uri'] . '" and media id "' . $mediaFiles[0]['mediaId'] . '" cannot be downloaded / copied. The following request error occurred: Request failed',
                'parameters' => [
                    'entity' => 'media',
                    'sourceId' => $mediaFiles[0]['mediaId'],
                    'uri' => $mediaFiles[0]['uri'],
                ],
                'titleSnippet' => 'swag-migration.index.error.SWAG_MIGRATION_CANNOT_GET_FILE.title',
                'descriptionSnippet' => 'swag-migration.index.error.SWAG_MIGRATION_CANNOT_GET_FILE.description',
                'entity' => 'media',
                'sourceId' => $mediaFiles[0]['mediaId'],
                'runId' => $this->runId,
            ],
        ], $this->loggingService->getLoggingArray());
    }

    /**
     * @param list<array{mediaId: string, fileName: string, fileContent: ?string, uri: string}> $migrationMedia
     */
    private function createBase(array $migrationMedia): DummyHttpDownloadService
    {
        $migrationMediaFiles = [];
        foreach ($migrationMedia as $media) {
            $migrationMediaFiles[] = [
                'id' => Uuid::randomBytes(),
                'run_id' => Uuid::fromHexToBytes($this->runId),
                'media_id' => Uuid::fromHexToBytes($media['mediaId']),
                'file_name' => $media['fileName'],
                'file_size' => $media['fileContent'] === null ? 0 : \mb_strlen($media['fileContent']),
                'uri' => $media['uri'],
            ];
        }

        $dbalConnection = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('fetchAllAssociative')->willReturn($migrationMediaFiles);
        $dbalConnection->method('createQueryBuilder')->willReturn($queryBuilder);

        /** @var StaticEntityRepository<MediaCollection> $mediaFileRepo */
        $mediaFileRepo = new StaticEntityRepository(
            [],
            new MediaDefinition()
        );
        $fileSaver = $this->createMock(FileSaver::class);
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('getAsync')->willReturnCallback(function ($uri) use ($migrationMedia) {
            $fileContent = null;
            foreach ($migrationMedia as $mediaFile) {
                if ($mediaFile['uri'] === $uri) {
                    $fileContent = $mediaFile['fileContent'];

                    break;
                }
            }

            $promise = new Promise();
            if ($fileContent) {
                $response = $this->createMock(ResponseInterface::class);
                $stream = $this->createMock(StreamInterface::class);
                $stream->method('getContents')->willReturn($fileContent);
                $response->method('getBody')->willReturn($stream);

                $promise->resolve($response);
            } else {
                $requestException = new RequestException(
                    'Request failed',
                    $this->createMock(RequestInterface::class),
                );

                $promise->reject($requestException);
            }

            return $promise;
        });

        return new DummyHttpDownloadService(
            $dbalConnection,
            $mediaFileRepo,
            $fileSaver,
            $this->loggingService,
            $httpClient
        );
    }
}
