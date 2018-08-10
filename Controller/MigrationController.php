<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\AssetDownloadServiceInterface;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
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

    public function __construct(
        MigrationCollectServiceInterface $migrationCollectService,
        MigrationWriteServiceInterface $migrationWriteService,
        AssetDownloadServiceInterface $assetDownloadService
    ) {
        $this->migrationCollectService = $migrationCollectService;
        $this->migrationWriteService = $migrationWriteService;
        $this->assetDownloadService = $assetDownloadService;
    }

    /**
     * @Route("/api/v{version}/migration/check-connection", name="api.admin.migration.check-connection", methods={"POST"})
     */
    public function checkConnection(): JsonResponse
    {
        // TODO request against common API of 5.5 plugin
        return new JsonResponse(['success' => true]);
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

        $migrationContext = new MigrationContext($profile, $gateway, $entity, $credentials, $offset, $limit);
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
        $profile = $request->get('profile', '');
        $entity = $request->get('entity', '');

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
    public function downloadAssets(Request $request, Context $context): JsonResponse
    {
        $this->assetDownloadService->downloadAssets($context);

        return new JsonResponse(['success' => true]);
    }
}
