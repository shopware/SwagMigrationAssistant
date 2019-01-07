<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\ProgressState;
use Symfony\Component\HttpFoundation\Request;

class SwagMigrationAccessTokenService
{
    public const ACCESS_TOKEN_NAME = 'swagMigrationAccessToken';

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

    public function __construct(
        EntityRepositoryInterface $migrationRunRepo,
        EntityRepositoryInterface $profileRepo,
        MigrationDataFetcherInterface $migrationDataFetcher
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
        $this->profileRepo = $profileRepo;
        $this->migrationDataFetcher = $migrationDataFetcher;
    }

    public function takeoverMigration(string $runUuid, Context $context): string
    {
        $userId = \mb_strtoupper($context->getSourceContext()->getUserId());

        return $this->createMigrationAccessToken($runUuid, $userId, $context);
    }

    public function createMigrationRun(string $profileId, array $totals, array $additionalData, Context $context): ?ProgressState
    {
        if ($this->isMigrationRunning($context)) {
            return null;
        }

        $runId = $this->createPlainMigrationRun($profileId, $context);
        $environmentInformation = $this->getEnvironmentInformation($profileId, $context);
        $accessToken = $this->updateMigrationRun($runId, $profileId, $environmentInformation, $totals, $additionalData, $context);

        return new ProgressState(false, true, $runId, $accessToken);
    }

    public function validateMigrationAccessToken(string $runId, Request $request, Context $context): bool
    {
        $databaseToken = $this->getDatabaseToken($runId, $context);

        if ($databaseToken === null) {
            return true;
        }

        if ($request->request->has(self::ACCESS_TOKEN_NAME)) {
            $requestToken = $request->request->get(self::ACCESS_TOKEN_NAME);

            if ($requestToken === $databaseToken) {
                return true;
            }
        }

        return false;
    }

    private function updateMigrationRun(
        string $runId,
        string $profileId,
        EnvironmentInformation $environmentInformation,
        array $totals,
        array $additionalData,
        Context $context
    ): string {
        $userId = \mb_strtoupper($context->getSourceContext()->getUserId());
        $accessToken = $this->createMigrationAccessToken($runId, $userId, $context);
        $credentials = $this->getProfileCredentials($profileId, $context);
        $this->updateRunWithAdditionalData($runId, $credentials, $environmentInformation, $totals, $additionalData, $context);

        return $accessToken;
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

    private function getProfileCredentials(string $profileId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $profileId));
        $criteria->setLimit(1);

        /** @var SwagMigrationProfileEntity $profile */
        $profile = $this->profileRepo->search($criteria, $context)->first();

        return $profile->getCredentialFields();
    }

    private function createMigrationAccessToken(string $runId, string $userId, Context $context): string
    {
        $token = hash(
            'sha256',
            sprintf('%s_%s_%s', $runId, $userId, time())
        );

        $this->migrationRunRepo->update(
          [
            [
                'id' => $runId,
                'accessToken' => $token,
                'userId' => $userId,
            ],
          ],
          $context
        );

        return $token;
    }

    private function getDatabaseToken(string $runId, Context $context): ?string
    {
        $runCriteria = new Criteria();
        $runCriteria->addFilter(new EqualsFilter('id', $runId));
        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search($runCriteria, $context)->first();

        if ($run === null) {
            return null;
        }

        return $run->getAccessToken();
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

        $migrationContext = new MigrationContext(
            '',
            '',
            $profileName,
            $gateway,
            '',
            $credentials,
            0,
            0
        );

        return $this->migrationDataFetcher->getEnvironmentInformation($migrationContext);
    }
}
