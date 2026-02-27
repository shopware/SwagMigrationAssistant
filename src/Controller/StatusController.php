<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\ApiRouteScope;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\PlatformRequest;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\MigrationConnectionFactory;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [ApiRouteScope::ID]])]
#[Package('fundamentals@after-sales')]
class StatusController extends AbstractController
{
    /**
     * @internal
     *
     * @param EntityRepository<SwagMigrationConnectionCollection> $migrationConnectionRepo
     * @param EntityRepository<GeneralSettingCollection> $generalSettingRepo
     */
    public function __construct(
        private readonly MigrationDataFetcherInterface $migrationDataFetcher,
        private readonly RunServiceInterface $runService,
        private readonly DataSelectionRegistryInterface $dataSelectionRegistry,
        private readonly EntityRepository $migrationConnectionRepo,
        private readonly ProfileRegistryInterface $profileRegistry,
        private readonly GatewayRegistryInterface $gatewayRegistry,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly EntityRepository $generalSettingRepo,
        private readonly MigrationConnectionFactory $connectionFactory,
    ) {
    }

    #[Route(
        path: '/api/_action/migration/get-profile-information',
        name: 'api.admin.migration.get-profile-information',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_GET]
    )]
    public function getProfileInformation(Request $request): Response
    {
        $profileName = (string) $request->query->get('profileName');
        $gatewayName = (string) $request->query->get('gatewayName');

        if ($profileName === '') {
            throw RoutingException::missingRequestParameter('profileName');
        }

        if ($gatewayName === '') {
            throw RoutingException::missingRequestParameter('gatewayName');
        }

        $profiles = $this->profileRegistry->getProfiles();

        $currentProfile = null;
        foreach ($profiles as $profile) {
            if ($profile->getName() === $profileName) {
                $currentProfile = [
                    'name' => $profile->getName(),
                    'sourceSystemName' => $profile->getSourceSystemName(),
                    'version' => $profile->getVersion(),
                    'author' => $profile->getAuthorName(),
                    'icon' => $profile->getIconPath(),
                ];

                break;
            }
        }

        if ($currentProfile === null) {
            return new Response();
        }

        $profile = $this->profileRegistry->getProfile($profileName);
        $context = new MigrationContext(new SwagMigrationConnectionEntity());
        $context->setProfile($profile);

        $gateways = $this->gatewayRegistry->getGateways($context);

        $currentGateway = null;
        foreach ($gateways as $gateway) {
            if ($gateway->getName() === $gatewayName) {
                $currentGateway = [
                    'name' => $gateway->getName(),
                    'snippet' => $gateway->getSnippetName(),
                ];

                break;
            }
        }

        if ($currentGateway === null) {
            return new Response();
        }

        return new JsonResponse(
            [
                'profile' => $currentProfile,
                'gateway' => $currentGateway,
            ]
        );
    }

    #[Route(
        path: '/api/_action/migration/get-profiles',
        name: 'api.admin.migration.get-profiles',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_GET]
    )]
    public function getProfiles(): JsonResponse
    {
        $profiles = $this->profileRegistry->getProfiles();

        $returnProfiles = [];
        foreach ($profiles as $profile) {
            $returnProfiles[] = [
                'name' => $profile->getName(),
                'sourceSystemName' => $profile->getSourceSystemName(),
                'version' => $profile->getVersion(),
                'author' => $profile->getAuthorName(),
            ];
        }

        return new JsonResponse($returnProfiles);
    }

    #[Route(
        path: '/api/_action/migration/get-gateways',
        name: 'api.admin.migration.get-gateways',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_GET]
    )]
    public function getGateways(Request $request): JsonResponse
    {
        $profileName = (string) $request->query->get('profileName');

        if ($profileName === '') {
            throw RoutingException::missingRequestParameter('profileName');
        }

        $profile = $this->profileRegistry->getProfile($profileName);
        $context = new MigrationContext(new SwagMigrationConnectionEntity());
        $context->setProfile($profile);

        $gateways = $this->gatewayRegistry->getGateways($context);

        $gatewayNames = [];
        foreach ($gateways as $gateway) {
            $gatewayNames[] = [
                'name' => $gateway->getName(),
                'snippet' => $gateway->getSnippetName(),
            ];
        }

        return new JsonResponse($gatewayNames);
    }

    #[Route(
        path: '/api/_action/migration/update-connection-credentials',
        name: 'api.admin.migration.update-connection-credentials',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.editor']],
        methods: [Request::METHOD_POST]
    )]
    public function updateConnectionCredentials(Request $request, Context $context): Response
    {
        $connectionId = $request->request->getAlnum('connectionId');

        $credentialFields = $request->request->all('credentialFields');

        if ($connectionId === '') {
            throw RoutingException::missingRequestParameter('connectionId');
        }

        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->getEntities()->first();

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $this->runService->updateConnectionCredentials($context, $connectionId, $credentialFields);

        return new Response();
    }

    #[Route(
        path: '/api/_action/migration/data-selection',
        name: 'api.admin.migration.data-selection',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_GET]
    )]
    public function getDataSelection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->query->getAlnum('connectionId');

        if ($connectionId === '') {
            throw RoutingException::missingRequestParameter('connectionId');
        }

        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->getEntities()->first();

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);

        $environmentInformation = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext, $context);

        return new JsonResponse(\array_values($this->dataSelectionRegistry->getDataSelections($migrationContext, $environmentInformation)->getElements()));
    }

    #[Route(
        path: '/api/_action/migration/create-new-connection',
        name: 'api.admin.migration.create-new-connection',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.creator']],
        methods: [Request::METHOD_POST]
    )]
    public function createNewConnection(Request $request, Context $context): JsonResponse
    {
        $id = $request->request->getAlnum('connectionId');
        $connectionName = (string) $request->request->get('connectionName');
        $profileName = (string) $request->request->get('profileName');
        $gatewayName = (string) $request->request->get('gatewayName');
        $credentialFields = $request->request->all('credentialFields');

        if ($id === '') {
            throw RoutingException::missingRequestParameter('connectionId');
        }

        if ($connectionName === '') {
            throw RoutingException::missingRequestParameter('connectionName');
        }

        if ($profileName === '') {
            throw RoutingException::missingRequestParameter('profileName');
        }

        if ($gatewayName === '') {
            throw RoutingException::missingRequestParameter('gatewayName');
        }

        if (empty($credentialFields)) {
            throw RoutingException::missingRequestParameter('credentialFields');
        }

        $connectionEntity = new SwagMigrationConnectionEntity();
        $connectionEntity->setId($id);
        $connectionEntity->setName($connectionName);
        $connectionEntity->setProfileName($profileName);
        $connectionEntity->setGatewayName($gatewayName);
        $connectionEntity->setCredentialFields($credentialFields);

        $information = $this->connectionFactory->validate($connectionEntity, $context);
        $this->connectionFactory->persistNew($connectionEntity, $context);

        return new JsonResponse($information);
    }

    #[Route(
        path: '/api/_action/migration/check-connection',
        name: 'api.admin.migration.check-connection',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_POST]
    )]
    public function checkConnection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->getAlnum('connectionId');

        if ($connectionId === '') {
            throw RoutingException::missingRequestParameter('connectionId');
        }

        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->getEntities()->first();

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $credentialFields = $request->request->all('credentialFields');

        if (!empty($credentialFields)) {
            $connection->setCredentialFields($credentialFields);
        }

        $oldFingerprint = $connection->getSourceSystemFingerprint();
        $information = $this->connectionFactory->validate($connection, $context);

        if ($oldFingerprint !== $connection->getSourceSystemFingerprint()) {
            // fingerprint updated, persist change to DB
            $this->connectionFactory->update($connection, $context);
        }

        return new JsonResponse($information);
    }

    #[Route(
        path: '/api/_action/migration/start-migration',
        name: 'api.admin.migration.start-migration',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.creator']],
        methods: [Request::METHOD_POST]
    )]
    public function startMigration(Request $request, Context $context): Response
    {
        $dataSelectionNames = $request->request->all('dataSelectionNames');

        if (empty($dataSelectionNames)) {
            throw RoutingException::missingRequestParameter('dataSelectionNames');
        }

        try {
            $this->runService->startMigrationRun($dataSelectionNames, $context);
        } catch (\Throwable $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Returns the progress of the running migration run.
     * If no migration run is running, it returns the progress with the step status IDLE.
     *
     * After starting the migration run, the steps are as follows, if the migration run is not aborted:
     * IDLE -> FETCHING -> WRITING -> MEDIA_PROCESSING -> CLEANUP -> INDEXING -> WAITING_FOR_APPROVE -> IDLE
     *
     * If the migration run is aborted, the steps are as follows:
     * IDLE -> [FETCHING || WRITING || MEDIA_PROCESSING] -> ABORTING -> CLEANUP -> INDEXING -> IDLE
     */
    #[Route(
        path: '/api/_action/migration/get-state',
        name: 'api.admin.migration.get-state',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_GET]
    )]
    public function getState(Context $context): JsonResponse
    {
        return new JsonResponse($this->runService->getRunStatus($context));
    }

    #[Route(
        path: '/api/_action/migration/approve-finished',
        name: 'api.admin.migration.approveFinished',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.editor']],
        methods: [Request::METHOD_POST]
    )]
    public function approveFinishedMigration(Context $context): Response
    {
        try {
            $this->runService->approveFinishingMigration($context);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Abort the running migration.
     * If no migration run is running or the current migration is not in the FETCHING or WRITING or MEDIA_PROCESSING step, it returns a bad request response.
     */
    #[Route(
        path: '/api/_action/migration/abort-migration',
        name: 'api.admin.migration.abort-migration',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.editor']],
        methods: [Request::METHOD_POST]
    )]
    public function abortMigration(Context $context): Response
    {
        $this->runService->abortMigration($context);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(
        path: '/api/_action/migration/reset-checksums',
        name: 'api.admin.migration.reset-checksums',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.deleter']],
        methods: [Request::METHOD_POST]
    )]
    public function resetChecksums(Request $request, Context $context): Response
    {
        $connectionId = $request->request->getAlnum('connectionId');

        if ($connectionId === '') {
            throw RoutingException::missingRequestParameter('connectionId');
        }

        $this->runService->startCleanupMappingChecksums($connectionId, $context);

        return new Response();
    }

    #[Route(
        path: '/api/_action/migration/cleanup-migration-data',
        name: 'api.admin.migration.cleanup-migration-data',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.deleter']],
        methods: [Request::METHOD_POST]
    )]
    public function cleanupMigrationData(Context $context): Response
    {
        $this->runService->startTruncateMigrationData($context);

        return new Response();
    }

    #[Route(
        path: '/api/_action/migration/is-truncating-migration-data',
        name: 'api.admin.migration.get-reset-status',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_GET]
    )]
    public function isTruncatingMigrationData(Context $context): JsonResponse
    {
        $settings = $this->generalSettingRepo->search(new Criteria(), $context)->getEntities()->first();

        if ($settings === null) {
            return new JsonResponse(false);
        }

        return new JsonResponse($settings->isReset());
    }

    #[Route(
        path: '/api/_action/migration/is-resetting-checksums',
        name: 'api.admin.migration.is-resetting-checksums',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_GET]
    )]
    public function isResettingChecksums(Context $context): JsonResponse
    {
        $settings = $this->generalSettingRepo
            ->search(new Criteria(), $context)
            ->getEntities()
            ->first();

        if ($settings === null) {
            return new JsonResponse(false);
        }

        return new JsonResponse(
            $settings->isResettingChecksums()
        );
    }

    #[Route(
        path: '/api/_action/migration/resume-after-fixes',
        name: 'api.admin.migration.resume-after-fixes',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['admin']],
        methods: [Request::METHOD_POST]
    )]
    public function resumeAfterFixes(Context $context): Response
    {
        $this->runService->resumeAfterFixes($context);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
