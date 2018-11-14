<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\Asset\HttpAssetDownloadServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationWriteServiceInterface;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileStruct;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MigrationController extends Controller
{
    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var MigrationWriteServiceInterface
     */
    private $migrationWriteService;

    /**
     * @var HttpAssetDownloadServiceInterface
     */
    private $assetDownloadService;

    /**
     * @var MigrationEnvironmentServiceInterface
     */
    private $environmentService;

    /**
     * @var RepositoryInterface
     */
    private $migrationProfileRepo;

    /**
     * @var MigrationProgressServiceInterface
     */
    private $migrationProgressService;

    public function __construct(
        MigrationCollectServiceInterface $migrationCollectService,
        MigrationWriteServiceInterface $migrationWriteService,
        HttpAssetDownloadServiceInterface $assetDownloadService,
        MigrationEnvironmentServiceInterface $environmentService,
        RepositoryInterface $migrationProfileRepo,
        MigrationProgressServiceInterface $migrationProgressService
    ) {
        $this->migrationCollectService = $migrationCollectService;
        $this->migrationWriteService = $migrationWriteService;
        $this->assetDownloadService = $assetDownloadService;
        $this->environmentService = $environmentService;
        $this->migrationProfileRepo = $migrationProfileRepo;
        $this->migrationProgressService = $migrationProgressService;
    }

    /**
     * @Route("/api/v{version}/migration/check-connection", name="api.admin.migration.check-connection", methods={"POST"})
     */
    public function checkConnection(Request $request, Context $context): JsonResponse
    {
        $profileId = $request->get('profileId');

        if ($profileId === null) {
            throw new MigrationContextPropertyMissingException('profileId');
        }

        $readCriteria = new ReadCriteria([$profileId]);
        $profileCollection = $this->migrationProfileRepo->read($readCriteria, $context);
        /** @var SwagMigrationProfileStruct $profile */
        $profile = $profileCollection->get($profileId);

        /** @var string $profileName */
        $profileName = $profile->getProfile();
        /** @var string $gateway */
        $gateway = $profile->getGateway();
        $credentials = $profile->getCredentialFields();

        $migrationContext = new MigrationContext('', '', $profileName, $gateway, '', $credentials, 0, 0);

        $information = $this->environmentService->getEnvironmentInformation($migrationContext);

        return new JsonResponse($information);
    }

    /**
     * @Route("/api/v{version}/migration/fetch-data", name="api.admin.migration.fetch-data", methods={"POST"})
     *
     * @throws MigrationContextPropertyMissingException
     */
    public function fetchData(Request $request, Context $context): Response
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
        $this->migrationCollectService->fetchData($migrationContext, $context);

        return new Response();
    }

    /**
     * @Route("/api/v{version}/migration/write-data", name="api.admin.migration.write-data", methods={"POST"})
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

        $migrationContext = new MigrationContext($runUuid, '', '', '', $entity, [], $offset, $limit);
        $this->migrationWriteService->writeData($migrationContext, $context);

        return new Response();
    }

    /**
     * @Route("/api/v{version}/migration/fetch-media-uuids", name="api.admin.migration.fetch-media-uuids", methods={"GET"})
     */
    public function fetchMediaUuids(Request $request, Context $context): JsonResponse
    {
        $limit = $request->query->getInt('limit', 100);
        $runId = $request->query->get('runId');

        if ($runId === null) {
            throw new MigrationWorkloadPropertyMissingException('runId');
        }

        $mediaUuids = $this->assetDownloadService->fetchMediaUuids($runId, $context, $limit);

        return new JsonResponse(['mediaUuids' => $mediaUuids]);
    }

    /**
     * @Route("/api/v{version}/migration/download-assets", name="api.admin.migration.download-assets", methods={"POST"})
     *
     * @throws MigrationWorkloadPropertyMissingException
     */
    public function downloadAssets(Request $request, Context $context): JsonResponse
    {
        /** @var array $workload */
        $runId = $request->request->get('runId');
        $workload = $request->request->get('workload', []);
        $fileChunkByteSize = $request->request->getInt('fileChunkByteSize', 1000 * 1000);

        if ($runId === null) {
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

        $newWorkload = $this->assetDownloadService->downloadAssets($runId, $context, $workload, $fileChunkByteSize);

        return new JsonResponse(['workload' => $newWorkload]);
    }

    /**
     * @Route("/api/v{version}/migration/get-state", name="api.admin.migration.get-state", methods={"GET"})
     */
    public function getState(Context $context): JsonResponse
    {
        $state = $this->migrationProgressService->getProgress($context);

        return new JsonResponse($state);
    }
}
