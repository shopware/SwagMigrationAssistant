<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace unit\Migration\Connection\Fingerprint;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\MigrationFingerprintService;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;

#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationFingerprintService::class)]
class MigrationFingerprintServiceTest extends TestCase
{
    public function testCheckReturnsFalseForEmptyFingerprint(): void
    {
        $service = $this->createService();

        $result = $service->searchDuplicates(
            null,
            Context::createDefaultContext(),
            null,
        );

        static::assertFalse($result);
    }

    public function testCheckReturnsFalseWhenNoMatchingFingerprintFound(): void
    {
        $idSearchResult = new IdSearchResult(
            0,
            [],
            new Criteria(),
            Context::createDefaultContext(),
        );

        $service = $this->createService([$idSearchResult]);

        $result = $service->searchDuplicates(
            'non-existing-fingerprint',
            Context::createDefaultContext(),
            null,
        );

        static::assertFalse($result);
    }

    public function testCheckReturnsTrueWhenMatchingFingerprintFound(): void
    {
        $idSearchResult = new IdSearchResult(
            1,
            [],
            new Criteria(),
            Context::createDefaultContext(),
        );

        $service = $this->createService([$idSearchResult]);

        $result = $service->searchDuplicates(
            'existing-fingerprint',
            Context::createDefaultContext(),
            null,
        );

        static::assertTrue($result);
    }

    public function testCheckExcludesConnectionIdWhenProvided(): void
    {
        $connectionId = 'excluded-connection-id';

        /** @var MockObject&EntityRepository<SwagMigrationConnectionCollection> $connectionRepo */
        $connectionRepo = $this->createMock(EntityRepository::class);
        $connectionRepo->expects(static::once())
            ->method('searchIds')
            ->with(static::callback(function (Criteria $criteria) use ($connectionId) {
                static::assertCount(2, $criteria->getFilters());

                $query = $criteria->getFilters()[1];
                static::assertInstanceOf(NotFilter::class, $query);

                $filter = $query->getQueries()[0];
                static::assertInstanceOf(EqualsFilter::class, $filter);

                return $filter->getValue() === $connectionId;
            }));

        $service = new MigrationFingerprintService($connectionRepo);

        $result = $service->searchDuplicates(
            'existing-fingerprint',
            Context::createDefaultContext(),
            $connectionId,
        );

        static::assertFalse($result);
    }

    /**
     * @param IdSearchResult[] $idSearchResults
     */
    private function createService(array $idSearchResults = []): MigrationFingerprintService
    {
        /** @var StaticEntityRepository<SwagMigrationConnectionCollection> $connectionRepo */
        $connectionRepo = new StaticEntityRepository(
            $idSearchResults,
            new SwagMigrationConnectionDefinition()
        );

        return new MigrationFingerprintService($connectionRepo);
    }
}
