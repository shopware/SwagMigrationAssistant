<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace unit\Migration\Connection\Fingerprint\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider\Shopware5FingerprintProvider;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader as ApiEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader as LocalEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(Shopware5FingerprintProvider::class)]
class Shopware5FingerprintProviderTest extends TestCase
{
    public function testSupportsShopware5Profiles(): void
    {
        static::assertTrue(Shopware5FingerprintProvider::supports(Shopware54Profile::PROFILE_NAME));
        static::assertTrue(Shopware5FingerprintProvider::supports(Shopware55Profile::PROFILE_NAME));
        static::assertTrue(Shopware5FingerprintProvider::supports(Shopware56Profile::PROFILE_NAME));
        static::assertTrue(Shopware5FingerprintProvider::supports(Shopware57Profile::PROFILE_NAME));
    }

    public function testGeneratesNoFingerprintForEmptyCredentials(): void
    {
        $contextFactory = $this->createMock(MigrationContextFactoryInterface::class);
        $contextFactory->expects(static::never())->method('createByConnection');

        $provider = $this->createFingerprintProvider($contextFactory);

        $fingerprint = $provider->provide([], new SwagMigrationConnectionEntity());

        static::assertNull($fingerprint);
    }

    public function testGeneratesNoFingerprintWithUnknownGateway(): void
    {
        $contextFactory = $this->createMock(MigrationContextFactoryInterface::class);
        $contextFactory->expects(static::once())->method('createByConnection');

        $provider = $this->createFingerprintProvider($contextFactory);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setGatewayName('unknown_gateway');

        $fingerprint = $provider->provide(['some' => 'credentials'], $connection);

        static::assertNull($fingerprint);
    }

    public function testGeneratesFingerprintForLocalGateway(): void
    {
        $esdKey = Uuid::randomHex();
        $installationDate = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $localEnvironmentReader = $this->createMock(LocalEnvironmentReader::class);
        $localEnvironmentReader->expects(static::once())->method('read')
            ->willReturn([
                'environmentInformation' => ['config' => [
                    'esdKey' => $esdKey,
                    'installationDate' => $installationDate,
                ]]]);

        $provider = $this->createFingerprintProvider(
            localEnvironmentReader: $localEnvironmentReader
        );

        $connection = new SwagMigrationConnectionEntity();
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        $fingerprint = $provider->provide(['some' => 'credentials'], $connection);

        static::assertSame(Hasher::hash($esdKey . $installationDate), $fingerprint);
    }

    public function testGeneratesFingerprintForApiGateway(): void
    {
        $esdKey = Uuid::randomHex();
        $installationDate = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $localEnvironmentReader = $this->createMock(ApiEnvironmentReader::class);
        $localEnvironmentReader->expects(static::once())->method('read')
            ->willReturn([
                'config' => [
                    'esdKey' => $esdKey,
                    'installationDate' => $installationDate,
                ]]);

        $provider = $this->createFingerprintProvider(
            apiEnvironmentReader: $localEnvironmentReader
        );

        $connection = new SwagMigrationConnectionEntity();
        $connection->setGatewayName(ShopwareApiGateway::GATEWAY_NAME);

        $fingerprint = $provider->provide(['some' => 'credentials'], $connection);

        static::assertSame(Hasher::hash($esdKey . $installationDate), $fingerprint);
    }

    public function testGenerateNoFingerprintWhenDataIsMissing(): void
    {
        $localEnvironmentReader = $this->createMock(ApiEnvironmentReader::class);
        $localEnvironmentReader->expects(static::once())->method('read')
            ->willReturn([]);

        $provider = $this->createFingerprintProvider(
            apiEnvironmentReader: $localEnvironmentReader
        );

        $connection = new SwagMigrationConnectionEntity();
        $connection->setGatewayName(ShopwareApiGateway::GATEWAY_NAME);

        $fingerprint = $provider->provide(['some' => 'credentials'], $connection);

        static::assertNull($fingerprint);
    }

    private function createFingerprintProvider(
        (MockObject&MigrationContextFactoryInterface)|null $migrationContextFactory = null,
        (MockObject&ApiEnvironmentReader)|null $apiEnvironmentReader = null,
        (MockObject&LocalEnvironmentReader)|null $localEnvironmentReader = null,
    ): Shopware5FingerprintProvider {
        return new Shopware5FingerprintProvider(
            $migrationContextFactory ?? $this->createMock(MigrationContextFactoryInterface::class),
            $apiEnvironmentReader ?? $this->createMock(ApiEnvironmentReader::class),
            $localEnvironmentReader ?? $this->createMock(LocalEnvironmentReader::class),
        );
    }
}
