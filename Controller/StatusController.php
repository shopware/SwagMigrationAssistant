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
use Symfony\Component\Routing\Annotation\Route;

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

        /** @var array|mixed $credentialFields */
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

    #[Route(path: '/api/_action/migration/get-state', name: 'api.admin.migration.get-state', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function getState(Request $request, Context $context): JsonResponse
    {
        $state = $this->migrationProgressService->getProgress($request, $context);

        return new JsonResponse($state);
    }

    #[Route(path: '/api/_action/migration/create-migration', name: 'api.admin.migration.create-migration', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function createMigration(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->getAlnum('connectionId');

        /** @var array|mixed $dataSelectionIds */
        $dataSelectionIds = $request->request->all('dataSelectionIds');

        if ($connectionId === '') {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        if (empty($dataSelectionIds)) {
            throw new MigrationContextPropertyMissingException('dataSelectionIds');
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $state = $this->runService->createMigrationRun(
            $migrationContext,
            $dataSelectionIds,
            $context
        );

        if ($state === null) {
            return $this->getState($request, $context);
        }

        return new JsonResponse($state);
    }

    #[Route(path: '/api/_action/migration/takeover-migration', name: 'api.admin.migration.takeover-migration', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function takeoverMigration(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $accessToken = $this->runService->takeoverMigration($runUuid, $context);

        return new JsonResponse(['accessToken' => $accessToken]);
    }

    // Aborts an already running migration remotely.
    #[Route(path: '/api/_action/migration/abort-migration', name: 'api.admin.migration.abort-migration', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function abortMigration(Request $request, Context $context): Response
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $this->runService->abortMigration($runUuid, $context);

        return new Response();
    }

    #[Route(path: '/api/_action/migration/finish-migration', name: 'api.admin.migration.finish-migration', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function finishMigration(Request $request, Context $context): Response
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $this->runService->finishMigration($runUuid, $context);

        return new Response();
    }

    #[Route(path: '/api/_action/migration/assign-themes', name: 'api.admin.migration.assign-themes', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function assignThemes(Request $request, Context $context): Response
    {
        $runUuid = $request->request->getAlnum('runUuid');

        if ($runUuid === '') {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $this->runService->assignThemeToSalesChannel($runUuid, $context);

        return new Response();
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
