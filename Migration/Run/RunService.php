<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\ProgressState;
use SwagMigrationNext\Migration\Service\SwagMigrationAccessTokenService;

class RunService implements RunServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $profileRepo;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var SwagMigrationAccessTokenService
     */
    private $accessTokenService;

    public function __construct(
        EntityRepositoryInterface $migrationRunRepo,
        EntityRepositoryInterface $profileRepo,
        MigrationDataFetcherInterface $migrationDataFetcher,
        MappingServiceInterface $mappingService,
        SwagMigrationAccessTokenService $accessTokenService
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
        $this->profileRepo = $profileRepo;
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->accessTokenService = $accessTokenService;
        $this->mappingService = $mappingService;
    }

    public function takeoverMigration(string $runUuid, Context $context): string
    {
        return $this->accessTokenService->updateRunAccessToken($runUuid, $context);
    }

    public function createMigrationRun(string $profileId, array $totals, array $additionalData, Context $context): ?ProgressState
    {
        if ($this->isMigrationRunning($context)) {
            return null;
        }

        $runUuid = $this->createPlainMigrationRun($profileId, $context);
        $environmentInformation = $this->getEnvironmentInformation($profileId, $context);
        $accessToken = $this->accessTokenService->updateRunAccessToken($runUuid, $context);
        $this->mappingService->createSalesChannelMapping($profileId, $environmentInformation->getStructure(), $context);
        $this->updateMigrationRun($runUuid, $profileId, $environmentInformation, $totals, $additionalData, $context);

        return new ProgressState(false, true, $runUuid, $accessToken);
    }

    private function updateMigrationRun(
        string $runUuid,
        string $profileId,
        EnvironmentInformation $environmentInformation,
        array $totals,
        array $additionalData,
        Context $context
    ): void {
        $credentials = $this->getProfileCredentials($profileId, $context);

        if (empty($credentials)) {
            $credentials = [];
        }

        $this->updateRunWithAdditionalData($runUuid, $credentials, $environmentInformation, $totals, $additionalData, $context);
    }

    private function isMigrationRunning(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', SwagMigrationRunEntity::STATUS_RUNNING));
        $total = $this->migrationRunRepo->searchIds($criteria, $context)->getTotal();

        return $total > 0;
    }

    private function createPlainMigrationRun(string $profileId, Context $context): string
    {
        $writtenEvent = $this->migrationRunRepo->create(
            [
                [
                    'profileId' => $profileId,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                ],
            ],
            $context
        );

        $ids = $writtenEvent->getEventByDefinition(SwagMigrationRunDefinition::class)->getIds();

        return array_pop($ids);
    }

    private function getEnvironmentInformation(string $profileId, Context $context): EnvironmentInformation
    {
        $criteria = new Criteria([$profileId]);
        $profileCollection = $this->profileRepo->search($criteria, $context);
        /** @var SwagMigrationProfileEntity $profile */
        $profile = $profileCollection->get($profileId);

        $profileName = $profile->getProfile();
        $gateway = $profile->getGateway();
        $credentials = $profile->getCredentialFields();

        if (empty($credentials)) {
            $credentials = [];
        }

        $migrationContext = new MigrationContext(
            '',
            '',
            $profileName,
            $gateway,
            '',
            0,
            0,
            $credentials
        );

        return $this->migrationDataFetcher->getEnvironmentInformation($migrationContext);
    }

    private function getProfileCredentials(string $profileId, Context $context): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $profileId));
        $criteria->setLimit(1);

        /** @var SwagMigrationProfileEntity $profile */
        $profile = $this->profileRepo->search($criteria, $context)->first();

        return $profile->getCredentialFields();
    }

    private function updateRunWithAdditionalData(
        string $runId,
        array $credentials,
        EnvironmentInformation $environmentInformation,
        array $totals,
        array $additionalData,
        Context $context
    ): void {
        $this->migrationRunRepo->update(
            [
                [
                    'id' => $runId,
                    'totals' => $totals,
                    'environmentInformation' => $environmentInformation->jsonSerialize(),
                    'credentialFields' => $credentials,
                    'additionalData' => $additionalData,
                ],
            ],
            $context
        );
    }
}
