<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use SwagMigrationAssistant\Exception\MigrationIsRunningException;
use SwagMigrationAssistant\Migration\History\HistoryServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @RouteScope(scopes={"api"})
 */
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
     * @Route("/api/migration/get-grouped-logs-of-run", name="api.admin.migration.get-grouped-logs-of-run", methods={"GET"})
     * @Acl({"admin"})
     */
    public function getGroupedLogsOfRun(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->query->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MissingRequestParameterException('runUuid');
        }

        $cleanResult = $this->historyService->getGroupedLogsOfRun(
            $runUuid,
            $context
        );

        return new JsonResponse([
            'total' => \count($cleanResult),
            'items' => $cleanResult,
            'downloadUrl' => $this->generateUrl(
                'api.admin.migration.download-logs-of-run',
                ['version' => $request->get('version')],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
    }

    /**
     * @Route("/api/_action/migration/download-logs-of-run", name="api.admin.migration.download-logs-of-run", defaults={"auth_required"=false}, methods={"POST"})
     * @Acl({"admin"})
     */
    public function downloadLogsOfRun(Request $request, Context $context): StreamedResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MissingRequestParameterException('runUuid');
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

    /**
     * @Route("/api/_action/migration/clear-data-of-run", name="api.admin.migration.clear-data-of-run", methods={"POST"})
     * @Acl({"admin"})
     */
    public function clearDataOfRun(Request $request, Context $context): Response
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MissingRequestParameterException('runUuid');
        }

        if ($this->historyService->isMediaProcessing()) {
            throw new MigrationIsRunningException();
        }

        $this->historyService->clearDataOfRun($runUuid, $context);

        return new Response();
    }

    /**
     * @Route("/api/_action/migration/is-media-processing", name="api.admin.migration.clear-logs-of-run", methods={"GET"})
     * @Acl({"admin"})
     */
    public function isMediaProcessing(): JsonResponse
    {
        $result = $this->historyService->isMediaProcessing();

        return new JsonResponse($result);
    }
}
