<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\ApiRouteScope;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\PlatformRequest;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\History\HistoryServiceInterface;
use SwagMigrationAssistant\Migration\History\LogGroupingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [ApiRouteScope::ID]])]
#[Package('fundamentals@after-sales')]
class HistoryController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly HistoryServiceInterface $historyService,
        private readonly LogGroupingService $logGroupingService,
        private readonly int $maxLimit,
    ) {
    }

    #[Route(
        path: '/api/_action/migration/get-grouped-logs-of-run',
        name: 'api.admin.migration.get-grouped-logs-of-run',
        methods: [Request::METHOD_GET],
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']]
    )]
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
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
    }

    #[Route(
        path: '/api/_action/migration/download-logs-of-run',
        name: 'api.admin.migration.download-logs-of-run',
        methods: [Request::METHOD_POST],
        defaults: ['auth_required' => false, PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']]
    )]
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

    #[Route(
        path: '/api/_action/migration/get-log-groups',
        name: 'api.admin.migration.get-log-groups',
        methods: [Request::METHOD_GET],
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']]
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
        path: '/api/_action/migration/get-unresolved-logs-batch-information',
        name: 'api.admin.migration.get-unresolved-logs-batch-information',
        methods: [Request::METHOD_POST],
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']]
    )]
    public function getUnresolvedLogsBatchInformation(Request $request): JsonResponse
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

        $count = $this->logGroupingService->getUnresolvedLogsCountByCodeAndEntity(
            $runId,
            $code,
            $entityName,
            $fieldName,
            !empty($connectionId) ? $connectionId : null,
        );

        return new JsonResponse([
            'count' => $count,
            'limit' => $this->maxLimit,
        ]);
    }

    #[Route(
        path: '/api/_action/migration/get-log-entity-ids-without-fix',
        name: 'api.admin.migration.get-log-entity-ids-without-fix',
        methods: [Request::METHOD_POST],
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']]
    )]
    public function getLogEntityIdsWithoutFix(Request $request): JsonResponse
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

        $limit = $request->request->getInt('limit', $this->maxLimit);
        if ($limit <= 0 || $limit > $this->maxLimit) {
            throw MigrationException::invalidValueForLimitParameter($this->maxLimit);
        }

        $logEntityIds = $this->logGroupingService->getLogEntityIdsWithoutFixByCodeAndEntity(
            $runId,
            $code,
            $entityName,
            $fieldName,
            $limit,
            !empty($connectionId) ? $connectionId : null,
        );

        return new JsonResponse([
            'entityIds' => $logEntityIds,
        ]);
    }
}
