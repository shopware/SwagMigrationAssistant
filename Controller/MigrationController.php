<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Read\ReadCriteria;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Migration\AssetDownloadServiceInterface;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationEnvironmentServiceInterface;
use SwagMigrationNext\Migration\MigrationWriteServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @var AssetDownloadServiceInterface
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
        AssetDownloadServiceInterface $assetDownloadService,
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
        /** @var ArrayStruct $profile */
        $profile = $profileCollection->get($profileId);

        /** @var string $profileName */
        $profileName = $profile->get('profile');
        /** @var string $gateway */
        $gateway = $profile->get('gateway');
        $credentials = $profile->get('credentialFields');

        $migrationContext = new MigrationContext($profileName, $gateway, '', $credentials, 0, 0);

        $information = $this->environmentService->getEnvironmentInformation($migrationContext);

        return new JsonResponse(['success' => true, 'environmentInformation' => $information]);
    }

    /**
     * @Route("/api/v{version}/migration/fetch-data", name="api.admin.migration.fetch-data", methods={"POST"})
     *
     * @throws MigrationContextPropertyMissingException
     */
    public function fetchData(Request $request, Context $context): JsonResponse
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

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/v{version}/migration/write-data", name="api.admin.migration.write-data", methods={"POST"})
     *
     * @throws MigrationContextPropertyMissingException
     */
    public function writeData(Request $request, Context $context): JsonResponse
    {
        $profile = $request->get('profile');
        $entity = $request->get('entity');

        if ($profile === null) {
            throw new MigrationContextPropertyMissingException('profile');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        $migrationContext = new MigrationContext($profile, '', $entity, [], 0, 0);
        $this->migrationWriteService->writeData($migrationContext, $context);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/v{version}/migration/download-assets", name="api.admin.migration.download-assets", methods={"POST"})
     */
    public function downloadAssets(Context $context): JsonResponse
    {
        $this->assetDownloadService->downloadAssets($context);

        return new JsonResponse(['success' => true]);
    }
}
