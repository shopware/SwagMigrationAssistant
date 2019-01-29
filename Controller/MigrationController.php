<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationNext\Exception\ConnectionCredentialsMissingException;
use SwagMigrationNext\Exception\EntityNotExistsException;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\RunServiceInterface;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MediaFileProcessorServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationNext\Migration\Service\SwagMigrationAccessTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MigrationController extends AbstractController
{
    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationDataWriter;

    /**
     * @var MediaFileProcessorServiceInterface
     */
    private $mediaFileProcessorService;

    /**
     * @var MigrationProgressServiceInterface
     */
    private $migrationProgressService;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationConnectionRepo;

    /**
     * @var SwagMigrationAccessTokenService
     */
    private $accessTokenService;

    /**
     * @var RunServiceInterface
     */
    private $runService;

    /**
     * @var DataSelectionRegistryInterface
     */
    private $dataSelectionRegistry;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    public function __construct(
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationDataWriterInterface $migrationDataWriter,
        MediaFileProcessorServiceInterface $mediaFileProcessorService,
        MigrationProgressServiceInterface $migrationProgressService,
        SwagMigrationAccessTokenService $accessTokenService,
        RunServiceInterface $runService,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        EntityRepositoryInterface $migrationConnectionRepo,
        EntityRepositoryInterface $migrationRunRepo
    ) {
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationDataWriter = $migrationDataWriter;
        $this->mediaFileProcessorService = $mediaFileProcessorService;
        $this->migrationProgressService = $migrationProgressService;
        $this->accessTokenService = $accessTokenService;
        $this->runService = $runService;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->migrationConnectionRepo = $migrationConnectionRepo;
        $this->migrationRunRepo = $migrationRunRepo;
    }

    /**
     * @Route("/api/v{version}/_action/migration/takeover-migration", name="api.admin.migration.takeover-migration", methods={"POST"})
     */
    public function takeoverMigration(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $accessToken = $this->runService->takeoverMigration($runUuid, $context);

        return new JsonResponse(['accessToken' => $accessToken]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/check-connection", name="api.admin.migration.check-connection", methods={"POST"})
     */
    public function checkConnection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->get('connectionId');

        if ($connectionId === null) {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            throw new ConnectionCredentialsMissingException();
        }

        $migrationContext = new MigrationContext(
            '',
            $connection,
            '',
            0,
            0
        );

        $information = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext);

        return new JsonResponse($information);
    }

    /**
     * @Route("/api/v{version}/_action/migration/update-connection-credentials", name="api.admin.migration.update-connection-credentials", methods={"POST"})
     */
    public function updateConnectionCredentials(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->get('connectionId');
        $credentialFields = $request->request->get('credentialFields');

        if ($connectionId === null) {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $this->runService->updateConnectionCredentials($context, $connectionId, $credentialFields);

        return new JsonResponse([
            'success' => 'true',
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/fetch-data", name="api.admin.migration.fetch-data", methods={"POST"})
     */
    public function fetchData(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');
        $entity = $request->request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getConnection() === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $runUuid);
        }

        $migrationContext = new MigrationContext(
            $run->getId(),
            $run->getConnection(),
            $entity,
            $offset,
            $limit
        );
        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/write-data", name="api.admin.migration.write-data", methods={"POST"})
     */
    public function writeData(Request $request, Context $context): Response
    {
        $runUuid = $request->request->get('runUuid');
        $entity = $request->request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        $migrationContext = new MigrationContext($runUuid, null, $entity, $offset, $limit);
        $this->migrationDataWriter->writeData($migrationContext, $context);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/fetch-media-uuids", name="api.admin.migration.fetch-media-uuids", methods={"GET"})
     */
    public function fetchMediaUuids(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->query->get('runUuid');
        $limit = $request->query->getInt('limit', 100);

        if ($runUuid === null) {
            throw new MigrationWorkloadPropertyMissingException('runUuid');
        }

        $mediaUuids = $this->mediaFileProcessorService->fetchMediaUuids($runUuid, $context, $limit);

        return new JsonResponse(['mediaUuids' => $mediaUuids]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/process-media", name="api.admin.migration.process-media", methods={"POST"})
     */
    public function processMedia(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');
        $workload = $request->request->get('workload', []);
        $fileChunkByteSize = $request->request->getInt('fileChunkByteSize', 1000 * 1000);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if (\count($workload) === 0) {
            return new JsonResponse(['workload' => [], 'validToken' => true]);
        }

        foreach ($workload as $work) {
            if (!isset($work['uuid'])) {
                throw new MigrationWorkloadPropertyMissingException('uuid');
            }
            if (!isset($work['currentOffset'])) {
                throw new MigrationWorkloadPropertyMissingException('currentOffset');
            }
            if (!isset($work['state'])) {
                throw new MigrationWorkloadPropertyMissingException('state');
            }
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getConnection() === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $runUuid);
        }

        $migrationContext = new MigrationContext(
            $runUuid,
            $run->getConnection(),
            '',
            0,
            0
        );

        $newWorkload = $this->mediaFileProcessorService->processMediaFiles($migrationContext, $context, $workload, $fileChunkByteSize);

        return new JsonResponse(['workload' => $newWorkload, 'validToken' => true]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/get-state", name="api.admin.migration.get-state", methods={"POST"})
     */
    public function getState(Request $request, Context $context): JsonResponse
    {
        $state = $this->migrationProgressService->getProgress($request, $context);

        return new JsonResponse($state);
    }

    /**
     * @Route("/api/v{version}/_action/migration/create-migration", name="api.admin.migration.create-migration", methods={"POST"})
     */
    public function createMigration(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->get('connectionId');
        $dataSelectionIds = $request->request->get('dataSelectionIds');
        $state = null;

        if ($connectionId === null) {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        if (empty($dataSelectionIds)) {
            throw new MigrationContextPropertyMissingException('dataSelectionIds');
        }

        $state = $this->runService->createMigrationRun($connectionId, $dataSelectionIds, $context);

        if ($state === null) {
            return $this->getState($request, $context);
        }

        return new JsonResponse($state);
    }

    /**
     * @Route("/api/v{version}/_action/migration/data-selection", name="api.admin.migration.data-selection", methods={"GET"})
     */
    public function getDataSelection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->query->get('connectionId');

        if ($connectionId === null) {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $migrationContext = new MigrationContext(
            '',
            $connection,
            '',
            0,
            0
        );

        return new JsonResponse(array_values($this->dataSelectionRegistry->getDataSelections($migrationContext)->getElements()));
    }

    /**
     * @Route("/api/v{version}/_action/migration/update-write-progress", name="api.admin.migration.update-write-progress", methods={"POST"})
     */
    public function updateWriteProgress(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
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

    /**
     * @Route("/api/v{version}/_action/migration/update-media-files-progress", name="api.admin.migration.update-media-files-progress", methods={"POST"})
     */
    public function updateMediaFilesProgress(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
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
}
