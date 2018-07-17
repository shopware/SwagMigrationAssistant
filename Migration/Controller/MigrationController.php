<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MigrationController extends Controller
{
    /**
     * @var MigrationService
     */
    private $migrationService;

    public function __construct(MigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
    }

    /**
     * @Route("/api/migration/fetch-data", name="api.admin.migration.fetch-data")
     * @Method({"POST"})
     */
    public function fetchData(Request $request, Context $context): JsonResponse
    {
        $profileName = $request->get('profileName');
        $gatewayName = $request->get('gatewayName');
        $entityType = $request->get('entityType');
        $credentials = $request->get('credentials', []);

        $migrationContext = new MigrationContext($profileName, $gatewayName, $entityType, $credentials);
        $this->migrationService->fetchData($migrationContext, $context);

        return new JsonResponse(['success' => true]);
    }
}
