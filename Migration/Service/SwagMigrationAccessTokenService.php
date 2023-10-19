<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\Exception\InvalidContextSourceException;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\HttpFoundation\Request;

#[Package('services-settings')]
class SwagMigrationAccessTokenService
{
    final public const ACCESS_TOKEN_NAME = 'swagMigrationAccessToken';

    public function __construct(private readonly EntityRepository $migrationRunRepo)
    {
    }

    public function updateRunAccessToken(
        string $runId,
        Context $context
    ): string {
        $sourceContext = $context->getSource();
        $userId = null;
        if ($sourceContext instanceof AdminApiSource) {
            /**
             * @psalm-suppress PossiblyNullArgument
             */
            $userId = \mb_strtoupper((string) $sourceContext->getUserId());
        }

        if ($sourceContext instanceof SystemSource) {
            $userId = 'CLI';
        }

        if ($userId === null) {
            throw new InvalidContextSourceException(AdminApiSource::class, \get_class($context->getSource()));
        }

        return $this->createMigrationAccessToken($runId, $userId, $context);
    }

    public function invalidateRunAccessToken(string $runId, Context $context): void
    {
        $token = \hash(
            'sha256',
            \sprintf('invalid_%s', \time())
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
        $token = \hash(
            'sha256',
            \sprintf('%s_%s_%s', $runId, $userId, \time())
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
        $run = $this->migrationRunRepo->search($runCriteria, $context)->first();

        if (!$run instanceof SwagMigrationRunEntity) {
            return null;
        }

        return $run->getAccessToken();
    }
}
