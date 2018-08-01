<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
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

    public function __construct(
        MigrationCollectServiceInterface $migrationCollectService,
        MigrationWriteServiceInterface $migrationWriteService
    ) {
        $this->migrationCollectService = $migrationCollectService;
        $this->migrationWriteService = $migrationWriteService;
    }

    /**
     * @Route("/api/migration/fetch-data", name="api.admin.migration.fetch-data", methods={"POST"})
     */
    public function fetchData(Request $request, Context $context): JsonResponse
    {
        $profile = $request->get('profile', '');
        $gateway = $request->get('gateway', '');
        $entity = $request->get('entity', '');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);
        $credentials = $request->get('credentials', []);

        $migrationContext = new MigrationContext($profile, $gateway, $entity, $credentials, $offset, $limit);
        $this->migrationCollectService->fetchData($migrationContext, $context);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/api/migration/write-data", name="api.admin.migration.write-data", methods={"POST"})
     */
    public function writeData(Request $request, Context $context): JsonResponse
    {
        $profile = $request->get('profile', '');
        $entity = $request->get('entity', '');

        $migrationContext = new MigrationContext($profile, '', $entity, [], 0, 0);
        $this->migrationWriteService->writeData($migrationContext, $context);

        return new JsonResponse(['success' => true]);
    }
}
