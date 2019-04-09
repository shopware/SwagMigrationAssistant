<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationNext\Exception\ConnectionCredentialsMissingException;
use SwagMigrationNext\Exception\EntityNotExistsException;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\RunServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
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

    public function __construct(
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationProgressServiceInterface $migrationProgressService,
        RunServiceInterface $runService,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        EntityRepositoryInterface $migrationConnectionRepo
    ) {
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationProgressService = $migrationProgressService;
        $this->runService = $runService;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->migrationConnectionRepo = $migrationConnectionRepo;
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
        $environmentInformation = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext);

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

        $information = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext);

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
}
