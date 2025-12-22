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
use SwagMigrationAssistant\Migration\History\LogGroupingService;
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
    /**
     * @internal
     */
    public function __construct(
        private readonly HistoryServiceInterface $historyService,
        private readonly LogGroupingService $logGroupingService,
    ) {
    }

    #[Route(path: '/api/_action/migration/get-grouped-logs-of-run', name: 'api.admin.migration.get-grouped-logs-of-run', methods: ['GET'], defaults: ['_acl' => ['swag_migration.viewer']])]
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
            throw MigrationException::migrationProcessing();
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

        if (empty($runId)) {
            throw RoutingException::missingRequestParameter('runId');
        }

        $level = $request->query->getAlpha('level');

        if (empty($level)) {
            throw RoutingException::missingRequestParameter('level');
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 25);

        $sortBy = $request->query->getAlpha('sortBy') ?: 'count';
        $sortDirection = $request->query->getAlpha('sortDirection') ?: 'DESC';

        if (!\in_array(\strtoupper($sortDirection), ['ASC', 'DESC'], true)) {
            $sortDirection = 'DESC';
        }

        $filterCode = $request->query->get('filterCode');
        $filterStatus = $request->query->get('filterStatus');
        $filterEntity = $request->query->get('filterEntity');
        $filterField = $request->query->get('filterField');

        $result = $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            $runId,
            $level,
            $page,
            $limit,
            $sortBy,
            \strtoupper($sortDirection),
            \is_string($filterCode) && !empty($filterCode) ? $filterCode : null,
            \is_string($filterStatus) && !empty($filterStatus) ? $filterStatus : null,
            \is_string($filterEntity) && !empty($filterEntity) ? $filterEntity : null,
            \is_string($filterField) && !empty($filterField) ? $filterField : null,
        );

        return new JsonResponse($result);
    }

    #[Route(
        path: '/api/_action/migration/get-all-entity-ids',
        name: 'api.admin.migration.get-all-entity-ids',
        methods: ['POST'],
        defaults: ['_acl' => ['swag_migration.viewer']]
    )]
    public function getAllEntityIds(Request $request): JsonResponse
    {
        $runId = $request->request->getAlnum('runId');

        if (empty($runId)) {
            throw RoutingException::missingRequestParameter('runId');
        }

        $code = $request->request->get('code');

        if (!\is_string($code) || empty($code)) {
            throw RoutingException::missingRequestParameter('code');
        }

        $entityName = $request->request->get('entityName');

        if (!\is_string($entityName) || empty($entityName)) {
            throw RoutingException::missingRequestParameter('entityName');
        }

        $fieldName = $request->request->get('fieldName');

        if (!\is_string($fieldName) || empty($fieldName)) {
            throw RoutingException::missingRequestParameter('fieldName');
        }

        $connectionId = $request->request->getAlnum('connectionId');

        $offset = $request->request->getInt('offset', 0);
        $limit = $request->request->getInt('limit', 100);

        $logEntityIds = $this->logGroupingService->getAllLogEntityIdsByCodeAndEntity(
            $runId,
            $code,
            $entityName,
            $fieldName,
            !empty($connectionId) ? $connectionId : null,
            $limit,
            $offset,
        );

        return new JsonResponse([
            'entityIds' => $logEntityIds,
        ]);
    }
}
