<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\History\HistoryServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class HistoryController extends AbstractController
{
    public function __construct(private readonly HistoryServiceInterface $historyService)
    {
    }

    #[Route(path: '/api/migration/get-grouped-logs-of-run', name: 'api.admin.migration.get-grouped-logs-of-run', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getGroupedLogsOfRun(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->query->getAlnum('runUuid');

        if ($runUuid === '') {
            throw RoutingException::missingRequestParameter('runUuid');
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

    #[Route(path: '/api/_action/migration/download-logs-of-run', name: 'api.admin.migration.download-logs-of-run', methods: ['POST'], defaults: ['auth_required' => false, '_acl' => ['admin']])]
    public function downloadLogsOfRun(Request $request, Context $context): StreamedResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw RoutingException::missingRequestParameter('runUuid');
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

    #[Route(path: '/api/_action/migration/clear-data-of-run', name: 'api.admin.migration.clear-data-of-run', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function clearDataOfRun(Request $request, Context $context): Response
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw RoutingException::missingRequestParameter('runUuid');
        }

        if ($this->historyService->isMediaProcessing()) {
            throw MigrationException::migrationIsAlreadyRunning();
        }

        $this->historyService->clearDataOfRun($runUuid, $context);

        return new Response();
    }

    #[Route(path: '/api/_action/migration/is-media-processing', name: 'api.admin.migration.is-media-processing', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function isMediaProcessing(): JsonResponse
    {
        $result = $this->historyService->isMediaProcessing();

        return new JsonResponse($result);
    }
}
