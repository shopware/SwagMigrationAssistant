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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\MigrationFingerprintService;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider\Shopware5FingerprintProvider;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider\Shopware6FingerprintProvider;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader as ApiEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader as LocalEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationFingerprintService::class)]
class MigrationFingerprintServiceTest extends TestCase
{
    private MockObject&Shopware5FingerprintProvider $shopware5Provider;

    private MockObject&Shopware6FingerprintProvider $shopware6Provider;

    protected function setUp(): void
    {
        $this->shopware5Provider = $this->getMockBuilder(Shopware5FingerprintProvider::class)
            ->setConstructorArgs([
                $this->createMock(MigrationContextFactoryInterface::class),
                $this->createMock(ApiEnvironmentReader::class),
                $this->createMock(LocalEnvironmentReader::class),
            ])
            ->onlyMethods(['provide'])
            ->getMock();

        $this->shopware6Provider = $this->getMockBuilder(Shopware6FingerprintProvider::class)
            ->setConstructorArgs([
                $this->createMock(MigrationContextFactoryInterface::class),
                $this->createMock(EnvironmentReader::class),
            ])
            ->onlyMethods(['provide'])
            ->getMock();
    }

    public function testGenerateNoFingerprintForEmptyCredentials(): void
    {
        $this->shopware5Provider->expects(static::never())->method('provide');
        $this->shopware6Provider->expects(static::never())->method('provide');

        $service = $this->createService();

        $fingerprint = $service->generate([], new SwagMigrationConnectionEntity());

        static::assertNull($fingerprint);
    }

    public function testGenerateNoFingerprintForUnsupportedProfile(): void
    {
        $this->shopware5Provider->expects(static::never())->method('provide');
        $this->shopware6Provider->expects(static::never())->method('provide');

        $service = $this->createService();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName('unsupported-profile');

        $fingerprint = $service->generate(['some' => 'data'], $connection);

        static::assertNull($fingerprint);
    }

    public function testGenerateFingerprintSuccessfullyForShopware5(): void
    {
        $expected = Uuid::randomHex();

        $this->shopware6Provider->expects(static::never())->method('provide');
        $this->shopware5Provider->expects(static::once())
            ->method('provide')
            ->willReturn($expected);

        $service = $this->createService();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware57Profile::PROFILE_NAME);

        $fingerprint = $service->generate(['some' => 'data'], $connection);

        static::assertSame($expected, $fingerprint);
    }

    public function testGenerateFingerprintSuccessfullyForShopware6(): void
    {
        $expected = Uuid::randomHex();

        $this->shopware5Provider->expects(static::never())->method('provide');
        $this->shopware6Provider->expects(static::once())
            ->method('provide')
            ->willReturn($expected);

        $service = $this->createService();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware6MajorProfile::PROFILE_NAME);

        $fingerprint = $service->generate(['some' => 'data'], $connection);

        static::assertSame($expected, $fingerprint);
    }

    public function testReturnNullWhenProviderThrowsException(): void
    {
        $this->shopware5Provider->expects(static::never())->method('provide');
        $this->shopware6Provider->expects(static::once())
            ->method('provide')
            ->willThrowException(new \RuntimeException('Error generating fingerprint'));

        $service = $this->createService();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware6MajorProfile::PROFILE_NAME);

        $fingerprint = $service->generate(['some' => 'data'], $connection);

        static::assertNull($fingerprint);
    }

    public function testCheckReturnsFalseForEmptyFingerprint(): void
    {
        $service = $this->createService();

        $result = $service->check(
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

        $result = $service->check(
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

        $result = $service->check(
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

        $service = new MigrationFingerprintService(
            [
                $this->shopware5Provider,
                $this->shopware6Provider,
            ],
            $connectionRepo,
        );

        $result = $service->check(
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

        return new MigrationFingerprintService(
            [
                $this->shopware5Provider,
                $this->shopware6Provider,
            ],
            $connectionRepo,
        );
    }
}
