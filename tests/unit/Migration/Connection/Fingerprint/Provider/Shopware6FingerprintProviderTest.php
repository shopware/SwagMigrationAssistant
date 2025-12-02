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
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider\Shopware6FingerprintProvider;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(Shopware6FingerprintProvider::class)]
class Shopware6FingerprintProviderTest extends TestCase
{
    public function testSupportsShopwareProfile(): void
    {
        static::assertTrue(Shopware6FingerprintProvider::supports(Shopware6MajorProfile::PROFILE_NAME));
    }

    public function testGenerateNoFingerprintWithEmptyCredentials(): void
    {
        $provider = $this->createFingerprintProvider();

        $fingerprint = $provider->provide(null, new SwagMigrationConnectionEntity());

        static::assertNull($fingerprint);
    }

    public function testGenerateFingerprint(): void
    {
        $shopId = Uuid::randomHex();

        $environmentReader = $this->createMock(EnvironmentReader::class);
        $environmentReader->expects(static::once())
            ->method('read')
            ->willReturn([
                'environmentInformation' => [
                    'shopIdV2' => $shopId,
                ],
            ]);

        $provider = $this->createFingerprintProvider(environmentReader: $environmentReader);

        $fingerprint = $provider->provide(
            ['some' => 'credentials'],
            new SwagMigrationConnectionEntity()
        );

        static::assertSame($shopId, $fingerprint);
    }

    public function testGeneratesNoFingerprintWhenShopIdMissing(): void
    {
        $environmentReader = $this->createMock(EnvironmentReader::class);
        $environmentReader->expects(static::once())
            ->method('read')
            ->willReturn([
                'environmentInformation' => [],
            ]);

        $provider = $this->createFingerprintProvider(environmentReader: $environmentReader);

        $fingerprint = $provider->provide(
            ['some' => 'credentials'],
            new SwagMigrationConnectionEntity()
        );

        static::assertNull($fingerprint);
    }

    private function createFingerprintProvider((MockObject&EnvironmentReader)|null $environmentReader = null): Shopware6FingerprintProvider
    {
        return new Shopware6FingerprintProvider(
            $this->createMock(MigrationContextFactoryInterface::class),
            $environmentReader ?? $this->createMock(EnvironmentReader::class),
        );
    }
}
