<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
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
    private $migrationProfileRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepository;

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

    public function __construct(
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationDataWriterInterface $migrationDataWriter,
        MediaFileProcessorServiceInterface $mediaFileProcessorService,
        MigrationProgressServiceInterface $migrationProgressService,
        SwagMigrationAccessTokenService $accessTokenService,
        RunServiceInterface $runService,
        EntityRepositoryInterface $migrationProfileRepo,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        EntityRepositoryInterface $migrationRunRepository
    ) {
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationDataWriter = $migrationDataWriter;
        $this->mediaFileProcessorService = $mediaFileProcessorService;
        $this->migrationProgressService = $migrationProgressService;
        $this->accessTokenService = $accessTokenService;
        $this->runService = $runService;
        $this->migrationProfileRepo = $migrationProfileRepo;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->migrationRunRepository = $migrationRunRepository;
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
        $profileId = $request->get('profileId');

        if ($profileId === null) {
            throw new MigrationContextPropertyMissingException('profileId');
        }

        $criteria = new Criteria([$profileId]);
        $profileCollection = $this->migrationProfileRepo->search($criteria, $context);
        /** @var SwagMigrationProfileEntity $profile */
        $profile = $profileCollection->get($profileId);

        /** @var string $profileName */
        $profileName = $profile->getProfile();
        /** @var string $gateway */
        $gateway = $profile->getGateway();
        $credentials = $profile->getCredentialFields();

        $migrationContext = new MigrationContext(
            '',
            '',
            $profileName,
            $gateway,
            '',
            0,
            0,
            $credentials
        );

        $information = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext);

        return new JsonResponse($information);
    }

    /**
     * @Route("/api/v{version}/_action/migration/fetch-data", name="api.admin.migration.fetch-data", methods={"POST"})
     *
     * @throws MigrationContextPropertyMissingException
     */
    public function fetchData(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');
        $profileId = $request->request->get('profileId');
        $profileName = $request->request->get('profileName');
        $gateway = $request->request->get('gateway');
        $entity = $request->request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);
        $credentials = $request->request->get('credentialFields', []);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($profileId === null) {
            throw new MigrationContextPropertyMissingException('profileId');
        }

        if ($profileName === null) {
            throw new MigrationContextPropertyMissingException('profileName');
        }

        if ($gateway === null) {
            throw new MigrationContextPropertyMissingException('gateway');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (empty($credentials)) {
            throw new MigrationContextPropertyMissingException('credentialFields');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        $migrationContext = new MigrationContext(
            $runUuid,
            $profileId,
            $profileName,
            $gateway,
            $entity,
            $offset,
            $limit,
            $credentials
        );
        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/write-data", name="api.admin.migration.write-data", methods={"POST"})
     *
     * @throws MigrationContextPropertyMissingException
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

        $migrationContext = new MigrationContext($runUuid, '', '', '', $entity, $offset, $limit);
        $this->migrationDataWriter->writeData($migrationContext, $context);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/fetch-media-uuids", name="api.admin.migration.fetch-media-uuids", methods={"GET"})
     *
     * @throws MigrationWorkloadPropertyMissingException
     */
    public function fetchMediaUuids(Request $request, Context $context): JsonResponse
    {
        $limit = $request->query->getInt('limit', 100);
        $runUuid = $request->query->get('runId');

        if ($runUuid === null) {
            throw new MigrationWorkloadPropertyMissingException('runId');
        }

        $mediaUuids = $this->mediaFileProcessorService->fetchMediaUuids($runUuid, $context, $limit);

        return new JsonResponse(['mediaUuids' => $mediaUuids]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/process-media", name="api.admin.migration.process-media", methods={"POST"})
     *
     * @throws MigrationWorkloadPropertyMissingException
     * @throws MigrationContextPropertyMissingException
     */
    public function processMedia(Request $request, Context $context): JsonResponse
    {
        /** @var array $workload */
        $runUuid = $request->request->get('runId');
        $profileId = $request->request->get('profileId');
        $profileName = $request->request->get('profileName');
        $gateway = $request->request->get('gateway');
        $workload = $request->request->get('workload', []);
        $fileChunkByteSize = $request->request->getInt('fileChunkByteSize', 1000 * 1000);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runId');
        }

        if ($profileId === null) {
            throw new MigrationContextPropertyMissingException('profileId');
        }

        if ($profileName === null) {
            throw new MigrationContextPropertyMissingException('profileName');
        }

        if ($gateway === null) {
            throw new MigrationContextPropertyMissingException('gateway');
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

        if (\count($workload) === 0) {
            return new JsonResponse(['workload' => [], 'validToken' => true]);
        }

        $runCollection = $this->migrationRunRepository->search(new Criteria([$runUuid]), $context);
        /** @var SwagMigrationRunEntity $run */
        $run = $runCollection->get($runUuid);

        $credentials = [];
        if ($run !== null && $run->getCredentialFields()) {
            $credentials = $run->getCredentialFields();
        }

        $migrationContext = new MigrationContext(
            $runUuid,
            $profileId,
            $profileName,
            $gateway,
            '',
            0,
            0,
            $credentials
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
        $profileId = $request->request->get('profileId');
        $totals = $request->request->get('totals');
        $additionalData = $request->request->get('additionalData');
        $state = null;

        if ($profileId !== null && $totals !== null && $additionalData !== null) {
            $state = $this->runService->createMigrationRun($profileId, $totals, $additionalData, $context);
        }

        if ($state === null) {
            return $this->getState($request, $context);
        }

        return new JsonResponse($state);
    }

    /**
     * @Route("/api/v{version}/_action/migration/data-selection", name="api.admin.migration.data-selection", methods={"GET"})
     */
    public function getDataSelection(Request $request): JsonResponse
    {
        $profileName = $request->query->get('profileName');
        $gateway = $request->query->get('gateway');

        if ($profileName === null) {
            throw new MigrationContextPropertyMissingException('profileName');
        }

        if ($gateway === null) {
            throw new MigrationContextPropertyMissingException('gateway');
        }

        $migrationContext = new MigrationContext(
            '',
            '',
            $profileName,
            $gateway,
            '',
            0,
            0
        );

        return new JsonResponse($this->dataSelectionRegistry->getDataSelections($migrationContext)->getElements());
    }
}
