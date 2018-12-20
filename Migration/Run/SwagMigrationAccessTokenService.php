<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\EnvironmentInformation;
use Symfony\Component\HttpFoundation\Request;

class SwagMigrationAccessTokenService
{
    public const ACCESS_TOKEN_NAME = 'swagMigrationAccessToken';

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    public function __construct(
        EntityRepositoryInterface $migrationRunRepo
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
    }

    public function takeoverMigration(string $runUuid, Context $context): string
    {
        $userId = \mb_strtoupper($context->getSourceContext()->getUserId());

        return $this->createMigrationAccessToken($runUuid, $userId, $context);
    }

    public function startMigrationRun(string $profileId, Context $context, EnvironmentInformation $environmentInformation): SwagMigrationAccessTokenStruct
    {
        $userId = \mb_strtoupper($context->getSourceContext()->getUserId());
        $this->abortProcessingRun($context);
        $runId = $this->createMigrationRun($profileId, $context, $environmentInformation);
        $accessToken = $this->createMigrationAccessToken($runId, $userId, $context);

        return new SwagMigrationAccessTokenStruct($runId, $accessToken);
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

    private function abortProcessingRun(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', SwagMigrationRunEntity::STATUS_RUNNING));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1);
        $ids = $this->migrationRunRepo->searchIds($criteria, $context)->getIds();

        if (empty($ids)) {
            return;
        }

        $this->migrationRunRepo->update(
            [
                [
                    'id' => $ids[0],
                    'status' => SwagMigrationRunEntity::STATUS_ABORTED
                ]
            ],
            $context
        );
    }

    private function createMigrationRun(string $profileId, Context $context, EnvironmentInformation $environmentInformation): string
    {
        $writtenEvent = $this->migrationRunRepo->create(
            [
                [
                    'profileId' => $profileId,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'environmentInformation' => $environmentInformation->jsonSerialize()
                ],
            ],
            $context
        );

        $ids = $writtenEvent->getEventByDefinition(SwagMigrationRunDefinition::class)->getIds();

        return array_pop($ids);
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
}
