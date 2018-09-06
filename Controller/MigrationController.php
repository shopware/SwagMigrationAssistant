<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Read\ReadCriteria;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\Asset\HttpAssetDownloadServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationWriteServiceInterface;
use SwagMigrationNext\Profile\SwagMigrationProfileStruct;
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

    public function __construct(
        MigrationCollectServiceInterface $migrationCollectService,
        MigrationWriteServiceInterface $migrationWriteService,
        HttpAssetDownloadServiceInterface $assetDownloadService,
        MigrationEnvironmentServiceInterface $environmentService,
        RepositoryInterface $migrationProfileRepo
    ) {
        $this->migrationCollectService = $migrationCollectService;
        $this->migrationWriteService = $migrationWriteService;
        $this->assetDownloadService = $assetDownloadService;
        $this->environmentService = $environmentService;
        $this->migrationProfileRepo = $migrationProfileRepo;
    }

    /**
     * @Route("/api/v{version}/migration/check-connection", name="api.admin.migration.check-connection", methods={"POST"})
     */
    public function checkConnection(Request $request, Context $context): JsonResponse
    {
        $profileId = $request->get('profileId');

        if ($profileId === null) {
            throw new MigrationContextPropertyMissingException('profile ID');
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

        $migrationContext = new MigrationContext($profileName, $gateway, '', $credentials, 0, 0);

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
        $profile = $request->get('profile');
        $gateway = $request->get('gateway');
        $entity = $request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);
        $credentials = $request->get('credentialFields', []);
        $catalogId = $request->get('catalogId');
        $salesChannelId = $request->get('salesChannelId');

        if ($profile === null) {
            throw new MigrationContextPropertyMissingException('profile');
        }

        if ($gateway === null) {
            throw new MigrationContextPropertyMissingException('gateway');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (empty($credentials)) {
            throw new MigrationContextPropertyMissingException('credentials');
        }

        $migrationContext = new MigrationContext(
            $profile,
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
        $profile = $request->get('profile');
        $entity = $request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($profile === null) {
            throw new MigrationContextPropertyMissingException('profile');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        $migrationContext = new MigrationContext($profile, '', $entity, [], $offset, $limit);
        $this->migrationWriteService->writeData($migrationContext, $context);

        return new Response();
    }

    /**
     * @Route("/api/v{version}/migration/fetch-media-uuids", name="api.admin.migration.fetch-media-uuids", methods={"GET"})
     */
    public function fetchMediaUuids(Request $request, Context $context): JsonResponse
    {
        $offset = $request->query->getInt('offset');
        $limit = $request->query->getInt('limit', 100);
        $profile = $request->query->get('profile');

        if ($profile === null) {
            throw new MigrationWorkloadPropertyMissingException('profile');
        }

        $mediaUuids = $this->assetDownloadService->fetchMediaUuids($context, $profile, $offset, $limit);

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
        $workload = $request->request->get('workload', []);
        $fileChunkByteSize = $request->request->getInt('fileChunkByteSize', 1000 * 1000);

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

        $newWorkload = $this->assetDownloadService->downloadAssets($context, $workload, $fileChunkByteSize);

        return new JsonResponse(['workload' => $newWorkload]);
    }
}
