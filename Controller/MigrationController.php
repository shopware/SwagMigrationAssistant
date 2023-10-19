<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\EntityPartialIndexerService;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class MigrationController extends AbstractController
{
    public function __construct(
        private readonly MigrationDataFetcherInterface $migrationDataFetcher,
        private readonly MigrationDataConverterInterface $migrationDataConverter,
        private readonly MigrationDataWriterInterface $migrationDataWriter,
        private readonly MediaFileProcessorServiceInterface $mediaFileProcessorService,
        private readonly SwagMigrationAccessTokenService $accessTokenService,
        private readonly RunServiceInterface $runService,
        private readonly EntityRepository $migrationRunRepo,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly EntityPartialIndexerService $entityPartialIndexerService
    ) {
    }

    #[Route(path: '/api/_action/migration/fetch-data', name: 'api.admin.migration.fetch-data', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function fetchData(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');
        $entity = (string) $request->request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($entity === '') {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if (!$run instanceof SwagMigrationRunEntity) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getConnection() === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $runUuid);
        }

        $migrationContext = $this->migrationContextFactory->create($run, $offset, $limit, $entity);

        if ($migrationContext === null) {
            throw new EntityNotExistsException(MigrationContext::class, $runUuid);
        }

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);

        if (!empty($data)) {
            $this->migrationDataConverter->convert($data, $migrationContext, $context);
        }

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    #[Route(path: '/api/_action/migration/update-write-progress', name: 'api.admin.migration.update-write-progress', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function updateWriteProgress(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $writeProgress = $this->runService->calculateWriteProgress($run, $context);

        $this->migrationRunRepo->update([
            [
                'id' => $run->getId(),
                'progress' => $writeProgress,
            ],
        ], $context);

        return new JsonResponse($writeProgress);
    }

    #[Route(path: '/api/_action/migration/write-data', name: 'api.admin.migration.write-data', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function writeData(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');
        $entity = (string) $request->request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($entity === '') {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if (!$run instanceof SwagMigrationRunEntity) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $migrationContext = $this->migrationContextFactory->create($run, $offset, $limit, $entity);

        if ($migrationContext === null) {
            throw new EntityNotExistsException(MigrationContext::class, $runUuid);
        }

        $this->migrationDataWriter->writeData($migrationContext, $context);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    #[Route(path: '/api/_action/migration/update-media-files-progress', name: 'api.admin.migration.update-media-files-progress', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function updateMediaFilesProgress(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $mediaFilesProgress = $this->runService->calculateMediaFilesProgress($run, $context);

        $this->migrationRunRepo->update([
            [
                'id' => $run->getId(),
                'progress' => $mediaFilesProgress,
            ],
        ], $context);

        return new JsonResponse($mediaFilesProgress);
    }

    #[Route(path: '/api/_action/migration/process-media', name: 'api.admin.migration.process-media', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function processMedia(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');
        $fileChunkByteSize = $request->request->getInt('fileChunkByteSize', 1000 * 1000);
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if (!$run instanceof SwagMigrationRunEntity) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getConnection() === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $runUuid);
        }

        $migrationContext = $this->migrationContextFactory->create($run, $offset, $limit);

        if ($migrationContext === null) {
            throw new EntityNotExistsException(MigrationContext::class, $runUuid);
        }

        $this->mediaFileProcessorService->processMediaFiles($migrationContext, $context, $fileChunkByteSize);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    #[Route(path: '/api/_action/migration/indexing', name: 'api.action.migration.indexing', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function indexing(Request $request): JsonResponse
    {
        $lastIndexer = $request->get('lastIndexer');

        /** @var array{offset: int|null}|null $offset */
        $offset = $request->request->all('offset');
        $result = $this->entityPartialIndexerService->partial(
            \is_string($lastIndexer) ? $lastIndexer : null,
            !empty($offset) ? $offset : null
        );

        if (!$result) {
            return new JsonResponse(['done' => true]);
        }

        return new JsonResponse([
            'lastIndexer' => $result->getIndexer(),
            'offset' => $result->getOffset(),
        ]);
    }
}
