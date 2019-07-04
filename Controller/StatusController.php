<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationAssistant\Exception\ConnectionCredentialsMissingException;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationProgressServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    public function __construct(
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationProgressServiceInterface $migrationProgressService,
        RunServiceInterface $runService,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        EntityRepositoryInterface $migrationConnectionRepo,
        ProfileRegistryInterface $profileRegistry,
        GatewayRegistryInterface $gatewayRegistry
    ) {
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationProgressService = $migrationProgressService;
        $this->runService = $runService;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->migrationConnectionRepo = $migrationConnectionRepo;
        $this->profileRegistry = $profileRegistry;
        $this->gatewayRegistry = $gatewayRegistry;
    }

    /**
     * @Route("/api/v{version}/_action/migration/get-profiles", name="api.admin.migration.get-profiles", methods={"GET"})
     */
    public function getProfiles(): Response
    {
        $profiles = $this->profileRegistry->getProfiles();

        $profileNames = [];
        foreach ($profiles as $profile) {
            $profileNames[] = $profile->getName();
        }

        return new JsonResponse($profileNames);
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

        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName($profileName);

        $migrationContext = new MigrationContext($connection);
        $gateways = $this->gatewayRegistry->getGateways($migrationContext);

        $gatewayNames = [];
        foreach ($gateways as $gateway) {
            $gatewayNames[] = $gateway->getName();
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

        $migrationContext = new MigrationContext(
            $connection
        );
        $profile = $this->profileRegistry->getProfile($migrationContext);
        $migrationContext->setProfile($profile);
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);
        $migrationContext->setGateway($gateway);

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

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            throw new ConnectionCredentialsMissingException();
        }

        $migrationContext = new MigrationContext(
            $connection
        );
        $profile = $this->profileRegistry->getProfile($migrationContext);
        $migrationContext->setProfile($profile);
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);
        $migrationContext->setGateway($gateway);

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
        $state = null;

        if ($connectionId === null) {
            throw new MigrationContextPropertyMissingException('connectionId');
        }

        if (empty($dataSelectionIds)) {
            throw new MigrationContextPropertyMissingException('dataSelectionIds');
        }

        $state = $this->runService->createMigrationRun($connectionId, $dataSelectionIds, $context);

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
}
