<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\HttpFoundation\Request;

class SwagMigrationAccessTokenService
{
    public const ACCESS_TOKEN_NAME = 'swagMigrationAccessToken';

    /**
     * @var RepositoryInterface
     */
    private $migrationRunRepo;

    public function __construct(
        RepositoryInterface $migrationRunRepo
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
    }

    public function takeoverMigration(string $runUuid, Context $context): string
    {
        $userId = \mb_strtoupper($context->getSourceContext()->getUserId());

        return $this->createMigrationAccessToken($runUuid, $userId, $context);
    }

    public function startMigrationRun(string $profileId, Context $context): SwagMigrationAccessTokenStruct
    {
        $userId = \mb_strtoupper($context->getSourceContext()->getUserId());
        $runId = $this->createMigrationRun($profileId, $context);
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

    private function createMigrationRun(string $profileId, Context $context): string
    {
        $writtenEvent = $this->migrationRunRepo->create(
            [
                [
                    'profileId' => $profileId,
                    'status' => SwagMigrationRunStruct::STATUS_RUNNING,
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
        /* @var SwagMigrationRunStruct $run */
        $run = $this->migrationRunRepo->search($runCriteria, $context)->first();

        if ($run === null) {
            return null;
        }

        return $run->getAccessToken();
    }
}
