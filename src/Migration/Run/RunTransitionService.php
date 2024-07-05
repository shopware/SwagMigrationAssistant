<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('services-settings')]
class RunTransitionService implements RunTransitionServiceInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transitionToRunStep(string $runId, MigrationStep $step): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->update(SwagMigrationRunDefinition::ENTITY_NAME)
            ->set('step', ':step')
            ->where('id = :runId')
            ->andWhere('step != :protectedStep')
            ->setParameter('step', $step->value)
            ->setParameter('runId', Uuid::fromHexToBytes($runId))
            ->setParameter('protectedStep', self::PROTECTED_STEP->value);

        $queryBuilder
            ->executeStatement();
    }

    /**
     * {@inheritDoc}
     */
    public function forceTransitionToRunStep(string $runId, MigrationStep $step): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->update(SwagMigrationRunDefinition::ENTITY_NAME)
            ->set('step', ':step')
            ->where('id = :runId')
            ->setParameter('step', $step->value)
            ->setParameter('runId', Uuid::fromHexToBytes($runId));

        $queryBuilder
            ->executeStatement();
    }
}
