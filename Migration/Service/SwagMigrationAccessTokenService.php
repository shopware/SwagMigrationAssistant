<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Context\AdminApiSource;
use Shopware\Core\Framework\Context\Exception\InvalidContextSourceException;
use Shopware\Core\Framework\Context\SystemSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
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

    public function updateRunAccessToken(
        string $runId,
        Context $context
    ): string {
        $sourceContext = $context->getSource();
        if ($sourceContext instanceof AdminApiSource) {
            $userId = \mb_strtoupper($sourceContext->getUserId());
        }

        if ($sourceContext instanceof SystemSource) {
            $userId = 'CLI';
        }

        if (!isset($userId)) {
            throw new InvalidContextSourceException(AdminApiSource::class, \get_class($context->getSource()));
        }

        return $this->createMigrationAccessToken($runId, $userId, $context);
    }

    public function invalidateRunAccessToken(string $runId, Context $context): void
    {
        $token = hash(
            'sha256',
            sprintf('invalid_%s', time())
        );

        $this->migrationRunRepo->update(
            [
                [
                    'id' => $runId,
                    'accessToken' => $token,
                    'status' => SwagMigrationRunEntity::STATUS_ABORTED,
                ],
            ],
            $context
        );
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
