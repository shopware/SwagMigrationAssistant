<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\Asset\HttpAssetDownloadServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Migration\Run\SwagMigrationAccessTokenService;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
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
     * @var HttpAssetDownloadServiceInterface
     */
    private $assetDownloadService;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationProfileRepo;

    /**
     * @var MigrationProgressServiceInterface
     */
    private $migrationProgressService;

    /**
     * @var SwagMigrationAccessTokenService
     */
    private $migrationAccessTokenService;

    public function __construct(
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationDataWriterInterface $migrationDataWriter,
        HttpAssetDownloadServiceInterface $assetDownloadService,
        EntityRepositoryInterface $migrationProfileRepo,
        MigrationProgressServiceInterface $migrationProgressService,
        SwagMigrationAccessTokenService $migrationAccessTokenService
    ) {
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationDataWriter = $migrationDataWriter;
        $this->assetDownloadService = $assetDownloadService;
        $this->migrationProfileRepo = $migrationProfileRepo;
        $this->migrationProgressService = $migrationProgressService;
        $this->migrationAccessTokenService = $migrationAccessTokenService;
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

        $accessToken = $this->migrationAccessTokenService->takeoverMigration($runUuid, $context);

        return new JsonResponse(['accessToken' => $accessToken]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/start-migration", name="api.admin.migration.start-migration", methods={"POST"})
     */
    public function startMigration(Request $request, Context $context): JsonResponse
    {
        $profileId = $request->request->get('profileId');

        if ($profileId === null) {
            throw new MigrationContextPropertyMissingException('profileId');
        }

        $runTokenStruct = $this->migrationAccessTokenService->startMigrationRun($profileId, $context);

        return new JsonResponse($runTokenStruct);
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

        $readCriteria = new Criteria([$profileId]);
        $profileCollection = $this->migrationProfileRepo->search($readCriteria, $context);
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
            $credentials,
            0,
            0
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
        $catalogId = $request->request->get('catalogId');
        $salesChannelId = $request->request->get('salesChannelId');

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

        if (!$this->migrationAccessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
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
            $credentials,
            $offset,
            $limit,
            $catalogId,
            $salesChannelId
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

        if (!$this->migrationAccessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        $migrationContext = new MigrationContext($runUuid, '', '', '', $entity, [], $offset, $limit);
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
        $limit = $request->query->getInt('limit', 100);
        $runUuid = $request->query->get('runId');

        if ($runUuid === null) {
            throw new MigrationWorkloadPropertyMissingException('runId');
        }

        $mediaUuids = $this->assetDownloadService->fetchMediaUuids($runUuid, $context, $limit);

        return new JsonResponse(['mediaUuids' => $mediaUuids]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/download-assets", name="api.admin.migration.download-assets", methods={"POST"})
     *
     * @throws MigrationWorkloadPropertyMissingException
     */
    public function downloadAssets(Request $request, Context $context): JsonResponse
    {
        /** @var array $workload */
        $runUuid = $request->request->get('runId');
        $workload = $request->request->get('workload', []);
        $fileChunkByteSize = $request->request->getInt('fileChunkByteSize', 1000 * 1000);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runId');
        }

        if (\count($workload) === 0) {
            return new JsonResponse(['workload' => []]);
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

        if (!$this->migrationAccessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        $newWorkload = $this->assetDownloadService->downloadAssets($runUuid, $context, $workload, $fileChunkByteSize);

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
}
