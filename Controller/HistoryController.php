<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\History\HistoryServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HistoryController extends AbstractController
{
    /**
     * @var HistoryServiceInterface
     */
    private $historyService;

    public function __construct(HistoryServiceInterface $historyService)
    {
        $this->historyService = $historyService;
    }

    /**
     * @Route("/api/v{version}/migration/get-grouped-logs-of-run", name="api.admin.migration.get-grouped-logs-of-run", methods={"GET"})
     */
    public function getGroupedLogsOfRun(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->query->get('runUuid');
        $offset = (int) $request->query->get('offset', 0);
        $limit = (int) $request->query->get('limit', 1);
        $sortBy = $request->query->get('sortBy', 'count');
        $sortDirection = $request->query->get('sortDirection', 'DESC');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $cleanResult = $this->historyService->getGroupedLogsOfRun(
            $runUuid,
            $offset,
            $limit,
            $sortBy,
            $sortDirection,
            $context
        );

        return new JsonResponse([
            'total' => count($cleanResult),
            'items' => $cleanResult,
            'downloadUrl' => $this->generateUrl(
                'api.admin.migration.download-logs-of-run',
                ['version' => $request->get('version')],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/download-logs-of-run", name="api.admin.migration.download-logs-of-run", defaults={"auth_required"=false}, methods={"POST"})
     */
    public function downloadLogsOfRun(Request $request, Context $context): StreamedResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $response = new StreamedResponse();
        $response->setCallback($this->historyService->downloadLogsOfRun(
            $runUuid,
            $context
        ));

        $filename = 'migrationRunLog-' . $runUuid . '.txt';
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        ));

        return $response;
    }
}
