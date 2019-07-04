<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MigrationController extends AbstractController
{
    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationDataWriter;

    /**
     * @var MediaFileProcessorServiceInterface
     */
    private $mediaFileProcessorService;

    /**
     * @var SwagMigrationAccessTokenService
     */
    private $accessTokenService;

    /**
     * @var RunServiceInterface
     */
    private $runService;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    /**
     * @var MigrationDataConverterInterface
     */
    private $migrationDataConverter;

    public function __construct(
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationDataConverterInterface $migrationDataConverter,
        MigrationDataWriterInterface $migrationDataWriter,
        MediaFileProcessorServiceInterface $mediaFileProcessorService,
        SwagMigrationAccessTokenService $accessTokenService,
        RunServiceInterface $runService,
        EntityRepositoryInterface $migrationRunRepo,
        ProfileRegistryInterface $profileRegistry,
        GatewayRegistryInterface $gatewayRegistry,
        DataSetRegistryInterface $dataSetRegistry
    ) {
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationDataConverter = $migrationDataConverter;
        $this->migrationDataWriter = $migrationDataWriter;
        $this->mediaFileProcessorService = $mediaFileProcessorService;
        $this->accessTokenService = $accessTokenService;
        $this->runService = $runService;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->profileRegistry = $profileRegistry;
        $this->gatewayRegistry = $gatewayRegistry;
        $this->dataSetRegistry = $dataSetRegistry;
    }

    /**
     * @Route("/api/v{version}/_action/migration/fetch-data", name="api.admin.migration.fetch-data", methods={"POST"})
     */
    public function fetchData(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');
        $entity = $request->request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getConnection() === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $runUuid);
        }

        $migrationContext = new MigrationContext(
            $run->getConnection(),
            $run->getId(),
            null,
            $offset,
            $limit
        );
        $profile = $this->profileRegistry->getProfile($migrationContext);
        $migrationContext->setProfile($profile);

        $gateway = $this->gatewayRegistry->getGateway($migrationContext);
        $migrationContext->setGateway($gateway);

        $dataSet = $this->dataSetRegistry->getDataSet($migrationContext, $entity);
        $migrationContext->setDataSet($dataSet);

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);

        if (!empty($data)) {
            $this->migrationDataConverter->convert($data, $migrationContext, $context);
        }

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/update-write-progress", name="api.admin.migration.update-write-progress", methods={"POST"})
     */
    public function updateWriteProgress(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $writeProgress = $this->runService->calculateWriteProgress($run, $context);

        $this->migrationRunRepo->update([
            [
                'id' => $run->getId(),
                'progress' => $writeProgress,
            ],
        ], $context);

        return new JsonResponse($writeProgress);
    }

    /**
     * @Route("/api/v{version}/_action/migration/write-data", name="api.admin.migration.write-data", methods={"POST"})
     */
    public function writeData(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');
        $entity = $request->request->get('entity');
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($entity === null) {
            throw new MigrationContextPropertyMissingException('entity');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $migrationContext = new MigrationContext($run->getConnection(), $runUuid, null, $offset, $limit);

        $profile = $this->profileRegistry->getProfile($migrationContext);
        $migrationContext->setProfile($profile);

        $gateway = $this->gatewayRegistry->getGateway($migrationContext);
        $migrationContext->setGateway($gateway);

        $dataSet = $this->dataSetRegistry->getDataSet($migrationContext, $entity);
        $migrationContext->setDataSet($dataSet);

        $this->migrationDataWriter->writeData($migrationContext, $context);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }

    /**
     * @Route("/api/v{version}/_action/migration/update-media-files-progress", name="api.admin.migration.update-media-files-progress", methods={"POST"})
     */
    public function updateMediaFilesProgress(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $mediaFilesProgress = $this->runService->calculateMediaFilesProgress($run, $context);

        $this->migrationRunRepo->update([
            [
                'id' => $run->getId(),
                'progress' => $mediaFilesProgress,
            ],
        ], $context);

        return new JsonResponse($mediaFilesProgress);
    }

    /**
     * @Route("/api/v{version}/_action/migration/process-media", name="api.admin.migration.process-media", methods={"POST"})
     */
    public function processMedia(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');
        $fileChunkByteSize = $request->request->getInt('fileChunkByteSize', 1000 * 1000);
        $offset = $request->request->getInt('offset');
        $limit = $request->request->getInt('limit', 250);

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if (!$this->accessTokenService->validateMigrationAccessToken($runUuid, $request, $context)) {
            return new JsonResponse([
                'validToken' => false,
            ]);
        }

        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getConnection() === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $runUuid);
        }

        $migrationContext = new MigrationContext(
            $run->getConnection(),
            $runUuid,
            null,
            $offset,
            $limit
        );

        $profile = $this->profileRegistry->getProfile($migrationContext);
        $migrationContext->setProfile($profile);

        $gateway = $this->gatewayRegistry->getGateway($migrationContext);
        $migrationContext->setGateway($gateway);

        $this->mediaFileProcessorService->processMediaFiles($migrationContext, $context, $fileChunkByteSize);

        return new JsonResponse([
            'validToken' => true,
        ]);
    }
}
