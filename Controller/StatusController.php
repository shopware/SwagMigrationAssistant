<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class StatusController extends AbstractController
{
    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MigrationProgressServiceInterface
     */
    private $migrationProgressService;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationConnectionRepo;

    /**
     * @var RunServiceInterface
     */
    private $runService;

    /**
     * @var DataSelectionRegistryInterface
     */
    private $dataSelectionRegistry;

    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    /**
     * @var MigrationContextFactoryInterface
     */
    private $migrationContextFactory;

    /**
     * @var EntityRepositoryInterface
     */
    private $generalSettingRepo;

    public function __construct(
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationProgressServiceInterface $migrationProgressService,
        RunServiceInterface $runService,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        EntityRepositoryInterface $migrationConnectionRepo,
        ProfileRegistryInterface $profileRegistry,
        GatewayRegistryInterface $gatewayRegistry,
        MigrationContextFactoryInterface $migrationContextFactory,
        EntityRepositoryInterface $generalSettingRepo
    ) {
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationProgressService = $migrationProgressService;
        $this->runService = $runService;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->migrationConnectionRepo = $migrationConnectionRepo;
        $this->profileRegistry = $profileRegistry;
        $this->gatewayRegistry = $gatewayRegistry;
        $this->migrationContextFactory = $migrationContextFactory;
        $this->generalSettingRepo = $generalSettingRepo;
    }

    /**
     * @Route("/api/v{version}/_action/migration/get-profile-information", name="api.admin.migration.get-profile-information", methods={"GET"})
     */
    public function getProfileInformation(Request $request): ?Response
    {
        $profileName = $request->query->get('profileName');
        $gatewayName = $request->query->get('gatewayName');

        if ($profileName === null) {
            throw new MigrationContextPropertyMissingException('profileName');
        }

        if ($gatewayName === null) {
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

    /**
     * @Route("/api/v{version}/_action/migration/get-profiles", name="api.admin.migration.get-profiles", methods={"GET"})
     */
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

    /**
     * @Route("/api/v{version}/_action/migration/get-gateways", name="api.admin.migration.get-gateways", methods={"GET"})
     */
    public function getGateways(Request $request): Response
    {
        $profileName = $request->query->get('profileName');

        if ($profileName === null) {
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

    /**
     * @Route("/api/v{version}/_action/migration/update-connection-credentials", name="api.admin.migration.update-connection-credentials", methods={"POST"})
     */
    public function updateConnectionCredentials(Request $request, Context $context): Response
    {
        $connectionId = $request->request->get('connectionId');
        $credentialFields = $request->request->get('credentialFields');

        if ($connectionId === null) {
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

    /**
     * @Route("/api/v{version}/_action/migration/data-selection", name="api.admin.migration.data-selection", methods={"GET"})
     */
    public function getDataSelection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->query->get('connectionId');

        if ($connectionId === null) {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$connectionId]), $context)->first();

        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $connectionId);
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);

        $environmentInformation = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext, $context);

        return new JsonResponse(array_values($this->dataSelectionRegistry->getDataSelections($migrationContext, $environmentInformation)->getElements()));
    }

    /**
     * @Route("/api/v{version}/_action/migration/check-connection", name="api.admin.migration.check-connection", methods={"POST"})
     */
    public function checkConnection(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->get('connectionId');

        if ($connectionId === null) {
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

    /**
     * @Route("/api/v{version}/_action/migration/get-state", name="api.admin.migration.get-state", methods={"POST"})
     */
    public function getState(Request $request, Context $context): JsonResponse
    {
        $state = $this->migrationProgressService->getProgress($request, $context);

        return new JsonResponse($state);
    }

    /**
     * @Route("/api/v{version}/_action/migration/create-migration", name="api.admin.migration.create-migration", methods={"POST"})
     */
    public function createMigration(Request $request, Context $context): JsonResponse
    {
        $connectionId = $request->request->get('connectionId');
        $dataSelectionIds = $request->request->get('dataSelectionIds');

        if ($connectionId === null) {
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
        $state = $this->runService->createMigrationRun($migrationContext, $dataSelectionIds, $context);

        if ($state === null) {
            return $this->getState($request, $context);
        }

        return new JsonResponse($state);
    }

    /**
     * @Route("/api/v{version}/_action/migration/takeover-migration", name="api.admin.migration.takeover-migration", methods={"POST"})
     */
    public function takeoverMigration(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $accessToken = $this->runService->takeoverMigration($runUuid, $context);

        return new JsonResponse(['accessToken' => $accessToken]);
    }

    /**
     * Aborts an already running migration remotely.
     *
     * @Route("/api/v{version}/_action/migration/abort-migration", name="api.admin.migration.abort-migration", methods={"POST"})
     */
    public function abortMigration(Request $request, Context $context): Response
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $this->runService->abortMigration($runUuid, $context);

        return new Response();
    }

    /**
     * @Route("/api/v{version}/_action/migration/finish-migration", name="api.admin.migration.finish-migration", methods={"POST"})
     */
    public function finishMigration(Request $request, Context $context): Response
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $this->runService->finishMigration($runUuid, $context);

        return new Response();
    }

    /**
     * @Route("/api/v{version}/_action/migration/assign-themes", name="api.admin.migration.assign-themes", methods={"POST"})
     */
    public function assignThemes(Request $request, Context $context): Response
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        $this->runService->assignThemeToSalesChannel($runUuid, $context);

        return new Response();
    }

    /**
     * @Route("/api/v{version}/_action/migration/reset-checksums", name="api.admin.migration.reset-checksums", methods={"POST"})
     */
    public function resetChecksums(Request $request, Context $context): Response
    {
        $connectionId = $request->request->get('connectionId');

        if ($connectionId === null) {
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

    /**
     * @Route("/api/v{version}/_action/migration/cleanup-migration-data", name="api.admin.migration.cleanup-migration-data", methods={"POST"})
     */
    public function cleanupMigrationData(): Response
    {
        $this->runService->cleanupMigrationData();

        return new Response();
    }

    /**
     * @Route("/api/v{version}/_action/migration/get-reset-status", name="api.admin.migration.get-reset-status", methods={"GET"})
     */
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
