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
use SwagMigrationAssistant\Migration\History\LogGroupingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('fundamentals@after-sales')]
class HistoryController extends AbstractController
{
    public function __construct(
        private readonly HistoryServiceInterface $historyService,
        private readonly LogGroupingServiceInterface $logGroupingService,
    ) {
    }

    #[Route(path: '/api/migration/get-grouped-logs-of-run', name: 'api.admin.migration.get-grouped-logs-of-run', methods: ['GET'], defaults: ['_acl' => ['swag_migration.viewer']])]
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

    #[Route(path: '/api/_action/migration/download-logs-of-run', name: 'api.admin.migration.download-logs-of-run', methods: ['POST'], defaults: ['auth_required' => false, '_acl' => ['swag_migration.viewer']])]
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

    #[Route(path: '/api/_action/migration/clear-data-of-run', name: 'api.admin.migration.clear-data-of-run', methods: ['POST'], defaults: ['_acl' => ['swag_migration.deleter']])]
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

    #[Route(path: '/api/_action/migration/is-media-processing', name: 'api.admin.migration.is-media-processing', methods: ['GET'], defaults: ['_acl' => ['swag_migration_history:read']])]
    public function isMediaProcessing(): JsonResponse
    {
        $result = $this->historyService->isMediaProcessing();

        return new JsonResponse($result);
    }

    #[Route(
        path: '/api/_action/migration/get-log-groups',
        name: 'api.admin.migration.get-log-groups',
        methods: ['GET'],
        defaults: ['_acl' => ['swag_migration.viewer']]
    )]
    public function getLogGroups(Request $request, Context $context): JsonResponse
    {
        $runId = $request->query->getAlnum('runId');
        $level = $request->query->get('level', '');
        $pageParam = $request->query->get('page');
        $limitParam = $request->query->get('limit');

        $sortBy = $request->query->get('sortBy', 'count');
        $sortDirection = $request->query->get('sortDirection', 'DESC');

        $filterCode = $request->query->get('filterCode');
        $filterStatus = $request->query->get('filterStatus');
        $filterEntity = $request->query->get('filterEntity');
        $filterField = $request->query->get('filterField');

        if (empty($runId) || empty($level)) {
            throw RoutingException::missingRequestParameter($runId === '' ? 'runId' : 'level');
        }

        if (!\is_numeric($pageParam)) {
            throw RoutingException::invalidRequestParameter('page');
        }

        if (!\is_numeric($limitParam)) {
            throw RoutingException::invalidRequestParameter('limit');
        }

        if (!\is_string($sortBy) || empty($sortBy)) {
            $sortBy = 'count';
        }

        if (!\is_string($sortDirection) || !\in_array(\strtoupper($sortDirection), ['ASC', 'DESC'], true)) {
            $sortDirection = 'DESC';
        }

        $result = $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            $runId,
            $level,
            (int) $pageParam,
            (int) $limitParam,
            $sortBy,
            \strtoupper($sortDirection),
            \is_string($filterCode) ? $filterCode : null,
            \is_string($filterStatus) ? $filterStatus : null,
            \is_string($filterEntity) ? $filterEntity : null,
            \is_string($filterField) ? $filterField : null,
            $context
        );

        return new JsonResponse($result);
    }

    #[Route(
        path: '/api/_action/migration/get-all-log-ids',
        name: 'api.admin.migration.get-all-log-ids',
        methods: ['POST'],
        defaults: ['_acl' => ['swag_migration.viewer']]
    )]
    public function getAllLogIds(Request $request): JsonResponse
    {
        $code = $request->request->get('code');
        $entityName = $request->request->get('entityName');
        $fieldName = $request->request->get('fieldName');

        if (!\is_string($code) || empty($code)) {
            throw RoutingException::missingRequestParameter('code');
        }

        if (!\is_string($entityName) || empty($entityName)) {
            throw RoutingException::missingRequestParameter('entityName');
        }

        if (!\is_string($fieldName) || empty($fieldName)) {
            throw RoutingException::missingRequestParameter('fieldName');
        }

        $logIds = $this->logGroupingService->getAllLogIdsByCodeAndEntity(
            $code,
            $entityName,
            $fieldName
        );

        return new JsonResponse([
            'ids' => $logIds,
        ]);
    }
}
