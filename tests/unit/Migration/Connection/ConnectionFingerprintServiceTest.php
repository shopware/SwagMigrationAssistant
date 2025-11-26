<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\unit\Migration\Connection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\ConnectionFingerprintService;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(ConnectionFingerprintService::class)]
class ConnectionFingerprintServiceTest extends TestCase
{
    /**
     * @var EntityRepository<SwagMigrationConnectionCollection>|MockObject
     */
    private EntityRepository|MockObject $connectionRepo;

    private ConnectionFingerprintService $connectionFingerprintService;

    protected function setUp(): void
    {
        $this->connectionRepo = $this->createMock(EntityRepository::class);

        $this->connectionFingerprintService = new ConnectionFingerprintService($this->connectionRepo);
    }

    public function testShouldNotGenerateFingerprintWithEmptyCredentials(): void
    {
        static::assertNull($this->connectionFingerprintService->generateFingerprint([], 'SomeGateway', 'SomeProfile'));
        static::assertNull($this->connectionFingerprintService->generateFingerprint(null, 'SomeGateway', 'SomeProfile'));
    }

    public function testShouldNotGenerateFingerprintWithUnknownGateway(): void
    {
        $credentials = [
            ConnectionFingerprintService::FIELD_KEY_ENDPOINT => 'https://example.com/api',
        ];

        static::assertNull($this->connectionFingerprintService->generateFingerprint($credentials, 'UnknownGateway', 'SomeProfile'));
        static::assertNotNull($this->connectionFingerprintService->generateFingerprint($credentials, ShopwareApiGateway::GATEWAY_NAME, 'SomeProfile'));
    }

    public function testShouldNotGenerateFingerprintWithMissingApiEndpoint(): void
    {
        $credentials = [
            'someOtherField' => 'someValue',
        ];

        static::assertNull($this->connectionFingerprintService->generateFingerprint($credentials, ShopwareApiGateway::GATEWAY_NAME, 'SomeProfile'));
    }

    public function testShouldNotGenerateFingerprintWithMissingDbHost(): void
    {
        $credentials = [
            ConnectionFingerprintService::FIELD_KEY_NAME => 'someDatabase',
            ConnectionFingerprintService::FIELD_KEY_PORT => '3306',
        ];

        static::assertNull($this->connectionFingerprintService->generateFingerprint($credentials, 'SomeLocalGateway', 'SomeProfile'));
    }

    public function testShouldNotGenerateFingerprintWithMissingDbName(): void
    {
        $credentials = [
            ConnectionFingerprintService::FIELD_KEY_HOST => 'localhost',
            ConnectionFingerprintService::FIELD_KEY_PORT => '3306',
        ];

        static::assertNull($this->connectionFingerprintService->generateFingerprint($credentials, 'SomeLocalGateway', 'SomeProfile'));
    }

    public function testShouldNormalizeLocalFingerprintDbCredentials(): void
    {
        $credentials1 = [
            ConnectionFingerprintService::FIELD_KEY_HOST => ' LOCALHOST  ',
            ConnectionFingerprintService::FIELD_KEY_NAME => ' someDatabase',
            ConnectionFingerprintService::FIELD_KEY_PORT => '3306',
        ];

        $credentials2 = [
            ConnectionFingerprintService::FIELD_KEY_HOST => '  loCalhOst',
            ConnectionFingerprintService::FIELD_KEY_NAME => 'someDatabase  ',
            // should use default port
        ];

        $fingerprint1 = $this->connectionFingerprintService->generateFingerprint($credentials1, 'SomeLocalGateway', 'SomeProfile');
        $fingerprint2 = $this->connectionFingerprintService->generateFingerprint($credentials2, 'SomeLocalGateway', 'SomeProfile');

        static::assertSame($fingerprint1, $fingerprint2);
    }

    public function testShouldIgnoreOrderOfCredentials(): void
    {
        $credentials1 = [
            ConnectionFingerprintService::FIELD_KEY_PORT => '3306',
            ConnectionFingerprintService::FIELD_KEY_NAME => 'someDatabase',
            ConnectionFingerprintService::FIELD_KEY_HOST => 'localhost',
        ];

        $credentials2 = [
            ConnectionFingerprintService::FIELD_KEY_NAME => 'someDatabase',
            ConnectionFingerprintService::FIELD_KEY_HOST => 'localhost',
            ConnectionFingerprintService::FIELD_KEY_PORT => '3306',
        ];

        $fingerprint1 = $this->connectionFingerprintService->generateFingerprint($credentials1, 'SomeLocalGateway', 'SomeProfile');
        $fingerprint2 = $this->connectionFingerprintService->generateFingerprint($credentials2, 'SomeLocalGateway', 'SomeProfile');

        static::assertSame($fingerprint1, $fingerprint2);
    }

    public function testShouldIgnoreExtraFieldsInCredentials(): void
    {
        $credentials1 = [
            ConnectionFingerprintService::FIELD_KEY_HOST => 'localhost',
            ConnectionFingerprintService::FIELD_KEY_NAME => 'someDatabase',
            ConnectionFingerprintService::FIELD_KEY_PORT => '3306',
            'extraField' => 'someValue',
        ];

        $credentials2 = [
            ConnectionFingerprintService::FIELD_KEY_HOST => 'localhost',
            ConnectionFingerprintService::FIELD_KEY_NAME => 'someDatabase',
            ConnectionFingerprintService::FIELD_KEY_PORT => '3306',
        ];

        $fingerprint1 = $this->connectionFingerprintService->generateFingerprint($credentials1, 'SomeLocalGateway', 'SomeProfile');
        $fingerprint2 = $this->connectionFingerprintService->generateFingerprint($credentials2, 'SomeLocalGateway', 'SomeProfile');

        static::assertSame($fingerprint1, $fingerprint2);
    }

    #[DataProvider('endpointVariationProvider')]
    public function testShouldNormalizeApiGatewayEndpoint(string $endpoint): void
    {
        $normalizedEndpoint = 'example.com/test/api';

        $credentialsA = [
            ConnectionFingerprintService::FIELD_KEY_ENDPOINT => $normalizedEndpoint,
        ];

        $credentialsB = [
            ConnectionFingerprintService::FIELD_KEY_ENDPOINT => $endpoint,
        ];

        $fingerprintA = $this->connectionFingerprintService->generateFingerprint($credentialsA, ShopwareApiGateway::GATEWAY_NAME, 'SomeProfile');
        $fingerprintB = $this->connectionFingerprintService->generateFingerprint($credentialsB, ShopwareApiGateway::GATEWAY_NAME, 'SomeProfile');

        static::assertSame($fingerprintA, $fingerprintB);
    }

    /**
     * @return iterable<string, array{endpoint: string}>
     */
    public static function endpointVariationProvider(): iterable
    {
        yield 'lowercase & uppercase' => [
            'endpoint' => 'exAmplE.coM/tEst/apI',
        ];

        yield 'trim' => [
            'endpoint' => ' example.com/test/api   ',
        ];

        yield 'ending slash' => [
            'endpoint' => 'example.com/test/api/',
        ];

        yield 'http' => [
            'endpoint' => 'http://example.com/test/api',
        ];

        yield 'https' => [
            'endpoint' => 'https://example.com/test/api',
        ];

        yield 'www' => [
            'endpoint' => 'www.example.com/test/api',
        ];

        yield 'already normalized' => [
            'endpoint' => 'example.com/test/api',
        ];

        yield 'complex variation' => [
            'endpoint' => '  HTTPS://WWW.Example.com/TEST/api/  ',
        ];
    }

    #[DataProvider('duplicateVariationProvider')]
    public function testShouldFindDuplicateConnections(bool $hasDuplicate, bool $expected, ?string $connectionId): void
    {
        $fingerprint = 'someFingerprint';
        $context = Context::createDefaultContext();

        $searchResult = new IdSearchResult(0, [], new Criteria(), $context);
        $searchCriteria = new Criteria();
        $searchCriteria->addFilter(new EqualsFilter('sourceSystemFingerprint', $fingerprint));

        if ($hasDuplicate) {
            $searchResult = IdSearchResult::fromIds(
                ['duplicateConnectionId'],
                new Criteria(),
                $context,
                1
            );
        }

        if ($connectionId !== null) {
            $searchCriteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('id', $connectionId),
            ]));
        }

        $this->connectionRepo
            ->expects(static::once())
            ->method('searchIds')
            ->with($searchCriteria, $context)
            ->willReturn($searchResult);

        $result = $this->connectionFingerprintService->hasDuplicateConnection(
            $fingerprint,
            Context::createDefaultContext(),
            $connectionId
        );

        static::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{hasDuplicate: bool, expected: bool, connectionId: string|null}>
     */
    public static function duplicateVariationProvider(): iterable
    {
        yield 'duplicate' => [
            'hasDuplicate' => true,
            'expected' => true,
            'connectionId' => null,
        ];

        yield 'no duplicate' => [
            'hasDuplicate' => false,
            'expected' => false,
            'connectionId' => null,
        ];

        yield 'exclude connection' => [
            'hasDuplicate' => true,
            'expected' => true,
            'connectionId' => 'existingConnectionId',
        ];

        yield 'not exclude connection' => [
            'hasDuplicate' => false,
            'expected' => false,
            'connectionId' => 'existingConnectionId',
        ];
    }
}
