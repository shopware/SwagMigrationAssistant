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
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;
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
        private readonly MigrationProgressServiceInterface $migrationProgressService,
        private readonly RunServiceInterface $runService,
        private readonly DataSelectionRegistryInterface $dataSelectionRegistry,
        private readonly EntityRepository $migrationConnectionRepo,
        private readonly ProfileRegistryInterface $profileRegistry,
        private readonly GatewayRegistryInterface $gatewayRegistry,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly EntityRepository $generalSettingRepo
    ) {
    }

    #[Route(path: '/api/_action/migration/get-profile-information', name: 'api.admin.migration.get-profile-information', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getProfileInformation(Request $request): ?Response
    {
        $profileName = (string) $request->query->get('profileName');
        $gatewayName = (string) $request->query->get('gatewayName');

        if ($profileName === '') {
            throw new MigrationContextPropertyMissingException('profileName');
        }

        if ($gatewayName === '') {
            throw new MigrationContextPropertyMissingException('gatewayName');
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

    #[Route(path: '/api/_action/migration/get-profiles', name: 'api.admin.migration.get-profiles', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getProfiles(): Response
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

    #[Route(path: '/api/_action/migration/get-gateways', name: 'api.admin.migration.get-gateways', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getGateways(Request $request): Response
    {
        $profileName = (string) $request->query->get('profileName');

        if ($profileName === '') {
            throw new MigrationContextPropertyMissingException('profileName');
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

    #[Route(path: '/api/_action/migration/update-connection-credentials', name: 'api.admin.migration.update-connection-credentials', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function updateConnectionCredentials(Request $request, Context $context): Response
    {
        $connectionId = $request->request->getAlnum('connectionId');

        $credentialFields = $request->request->all('credentialFields');

        if ($connectionId === '') {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $this->runService->updateConnectionCredentials($context, $connectionId, $credentialFields);

        return new Response();
    }

    #[Route(path: '/api/_action/migration/data-selection', name: 'api.admin.migration.data-selection', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getDataSelection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->query->getAlnum('connectionId');

        if ($connectionId === '') {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);

        $environmentInformation = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext, $context);

        return new JsonResponse(\array_values($this->dataSelectionRegistry->getDataSelections($migrationContext, $environmentInformation)->getElements()));
    }

    #[Route(path: '/api/_action/migration/check-connection', name: 'api.admin.migration.check-connection', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function checkConnection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->getAlnum('connectionId');

        if ($connectionId === '') {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $information = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext, $context);

        return new JsonResponse($information);
    }

    #[Route(path: '/api/_action/migration/start-migration', name: 'api.admin.migration.start-migration', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function startMigration(Request $request, Context $context): Response
    {
        // ToDo: MIG-895 it should create a new migration run
        // ToDo: MIG-895 implement me: this should submit the MQ job to start the migration

        return new Response(null, Response::HTTP_NO_CONTENT);

        // in case there is already a migration running
        // return new Response(null, Response::HTTP_BAD_REQUEST);
    }

    // ToDo: MIG-895 - build this in a new way. MQ should store progress that is easy to retrieve. Don't do heavy calculations here (will be polled)!
    #[Route(path: '/api/_action/migration/get-state', name: 'api.admin.migration.get-state', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getState(Request $request, Context $context): JsonResponse
    {
        // ToDo: MIG-895 remove the old way:
        $state = $this->migrationProgressService->getProgress($request, $context);

        // determine active runUuid

        $possibleStates = [
            'idle', // no migration running
            'fetching',
            'writing',
            'media-processing',
            'finished', // the MQ job is done, just inform the user about it (needs approval, see endpoint below)
        ];

        return new JsonResponse([
            'step' => $possibleStates[1],
            'progress' => 50,
            'total' => 1000,
        ]);
    }

    // ToDo: MIG-895 - build this in a new way. MQ should store progress that is easy to retrieve. Don't do heavy calculations here (will be polled)!
    #[Route(path: '/api/_action/migration/approve-finished', name: 'api.admin.migration.approveFinished', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function approveFinishedMigration(Request $request, Context $context): Response
    {
        // get state from above

        return new Response(null, Response::HTTP_NO_CONTENT);

        // in case there is no migration in 'finish' state
        // return new Response(null, Response::HTTP_BAD_REQUEST);
    }

    // ToDo: MIG-895 - Refactor this to stop the message queue job
    #[Route(path: '/api/_action/migration/abort-migration', name: 'api.admin.migration.abort-migration', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function abortMigration(Request $request, Context $context): Response
    {
        // ToDo: MIG-895 - Get the uuid from the running migration or return an error if no migration is running
        /*
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }
        */

        // $this->runService->abortMigration($runUuid, $context);

        return new Response(null, Response::HTTP_NO_CONTENT);

        // in case there is no running migration
        // return new Response(null, Response::HTTP_BAD_REQUEST);
    }

    #[Route(path: '/api/_action/migration/reset-checksums', name: 'api.admin.migration.reset-checksums', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function resetChecksums(Request $request, Context $context): Response
    {
        $connectionId = $request->request->getAlnum('connectionId');

        if ($connectionId === '') {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $this->runService->cleanupMappingChecksums($connectionId, $context);

        return new Response();
    }

    #[Route(path: '/api/_action/migration/cleanup-migration-data', name: 'api.admin.migration.cleanup-migration-data', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function cleanupMigrationData(): Response
    {
        $this->runService->cleanupMigrationData();

        return new Response();
    }

    #[Route(path: '/api/_action/migration/get-reset-status', name: 'api.admin.migration.get-reset-status', methods: ['GET'], defaults: ['_acl' => ['admin']])]
    public function getResetStatus(Context $context): JsonResponse
    {
        /** @var GeneralSettingEntity|null $settings */
        $settings = $this->generalSettingRepo->search(new Criteria(), $context)->first();

        if ($settings === null) {
            return new JsonResponse(false);
        }

        return new JsonResponse($settings->isReset());
    }
}
