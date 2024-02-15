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
use Shopware\Core\Framework\Routing\RoutingException;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
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

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class StatusController extends AbstractController
{
    /**
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
        private readonly EntityRepository $generalSettingRepo
    ) {
    }

    #[Route(
        path: '/api/_action/migration/get-profile-information',
        name: 'api.admin.migration.get-profile-information',
        defaults: ['_acl' => ['admin']],
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

        $migrationContext = $this->migrationContextFactory->createByProfileName($profileName);
        $gateways = $this->gatewayRegistry->getGateways($migrationContext);

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
        defaults: ['_acl' => ['admin']],
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
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function getGateways(Request $request): JsonResponse
    {
        $profileName = (string) $request->query->get('profileName');

        if ($profileName === '') {
            throw RoutingException::missingRequestParameter('profileName');
        }

        $migrationContext = $this->migrationContextFactory->createByProfileName($profileName);
        $gateways = $this->gatewayRegistry->getGateways($migrationContext);

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
        defaults: ['_acl' => ['admin']],
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
        defaults: ['_acl' => ['admin']],
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
        path: '/api/_action/migration/check-connection',
        name: 'api.admin.migration.check-connection',
        defaults: ['_acl' => ['admin']],
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

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $information = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext, $context);

        return new JsonResponse($information);
    }

    #[Route(
        path: '/api/_action/migration/start-migration',
        name: 'api.admin.migration.start-migration',
        defaults: ['_acl' => ['admin']],
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
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function getState(Context $context): JsonResponse
    {
        return new JsonResponse($this->runService->getRunStatus($context));
    }

    #[Route(
        path: '/api/_action/migration/approve-finished',
        name: 'api.admin.migration.approveFinished',
        defaults: ['_acl' => ['admin']],
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
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_POST]
    )]
    public function abortMigration(Context $context): Response
    {
        try {
            $this->runService->abortMigration($context);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(
        path: '/api/_action/migration/reset-checksums',
        name: 'api.admin.migration.reset-checksums',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_POST]
    )]
    public function resetChecksums(Request $request, Context $context): Response
    {
        $connectionId = $request->request->getAlnum('connectionId');

        if ($connectionId === '') {
            throw RoutingException::missingRequestParameter('connectionId');
        }

        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->getEntities()->first();

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        // ToDo: MIG-965 - Check how we could put this into the MQ
        $this->runService->cleanupMappingChecksums($connectionId, $context);

        return new Response();
    }

    #[Route(
        path: '/api/_action/migration/cleanup-migration-data',
        name: 'api.admin.migration.cleanup-migration-data',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_POST]
    )]
    public function cleanupMigrationData(Context $context): Response
    {
        $this->runService->cleanupMigrationData($context);

        return new Response();
    }

    #[Route(
        path: '/api/_action/migration/get-reset-status',
        name: 'api.admin.migration.get-reset-status',
        defaults: ['_acl' => ['admin']],
        methods: [Request::METHOD_GET]
    )]
    public function getResetStatus(Context $context): JsonResponse
    {
        $settings = $this->generalSettingRepo->search(new Criteria(), $context)->getEntities()->first();

        if ($settings === null) {
            return new JsonResponse(false);
        }

        return new JsonResponse($settings->isReset());
    }
}
