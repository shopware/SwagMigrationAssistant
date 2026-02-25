<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\MigrationFingerprintServiceInterface;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
readonly class MigrationConnectionFactory
{
    /**
     * @param EntityRepository<SwagMigrationConnectionCollection> $migrationConnectionRepo
     */
    public function __construct(
        private EntityRepository $migrationConnectionRepo,
        private MigrationContextFactoryInterface $migrationContextFactory,
        private MigrationDataFetcherInterface $migrationDataFetcher,
        private MigrationFingerprintServiceInterface $fingerprintService,
    ) {
    }

    /**
     * validates a given connection, checking:
     * - the connection name is unique
     * - the connection credentials are valid and the source system is reachable
     * - the fingerprint is unique
     * And then:
     * - updating the connection entity with the fingerprint
     * - returning the environment information of the source system.
     */
    public function validate(
        SwagMigrationConnectionEntity $connectionEntity,
        Context $context,
    ): EnvironmentInformation {
        // check that the connection name is unique
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $connectionEntity->getName()));
        $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('id', $connectionEntity->getId()),
        ]));
        $existingConnection = $this->migrationConnectionRepo->search($criteria, $context)->getEntities()->first();
        if ($existingConnection !== null) {
            throw MigrationException::connectionNameNotUnique();
        }

        // check source system reachable with given credentials
        $migrationContext = $this->migrationContextFactory->createByConnection($connectionEntity);
        $information = $this->migrationDataFetcher->getEnvironmentInformation($migrationContext, $context);
        $requestStatus = $information->getRequestStatus();
        if ($requestStatus !== null && $requestStatus->getCode() !== '') {
            throw MigrationException::connectionValidationFailed(
                $requestStatus->getCode(),
                $requestStatus->getMessage()
            );
        }

        // validate fingerprint uniqueness
        $fingerprint = $information->getFingerprint();
        if ($fingerprint !== null) {
            $connectionEntity->setSourceSystemFingerprint($fingerprint);
        }
        $hasDuplicate = $this->fingerprintService->searchDuplicates(
            $fingerprint,
            $context,
            $connectionEntity->getId(),
        );

        if ($hasDuplicate) {
            throw MigrationException::duplicateSourceConnection();
        }

        return $information;
    }

    /**
     * Persists a new connection entity in the DB.
     *
     * # Safety:
     * - you should have called @see validate() before calling this method
     * - the connection has to be new, otherwise this will throw
     */
    public function persistNew(SwagMigrationConnectionEntity $connectionEntity, Context $context): void
    {
        // scope needed because of WriteProtected `credentials` field
        $context->scope(
            MigrationContext::SOURCE_CONTEXT,
            function (Context $scopedContext) use ($connectionEntity): void {
                $this->migrationConnectionRepo->create([
                    $connectionEntity->jsonSerialize(),
                ], $scopedContext);
            }
        );
    }

    /**
     * # Safety:
     *  - you should have called @see validate() before calling this method
     */
    public function update(SwagMigrationConnectionEntity $connectionEntity, Context $context): void
    {
        // scope needed because of WriteProtected `credentials` field
        $context->scope(
            MigrationContext::SOURCE_CONTEXT,
            function (Context $scopedContext) use ($connectionEntity): void {
                $this->migrationConnectionRepo->update([
                    $connectionEntity->jsonSerialize(),
                ], $scopedContext);
            }
        );
    }
}
