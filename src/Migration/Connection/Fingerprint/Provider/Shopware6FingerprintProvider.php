<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class Shopware6FingerprintProvider implements MigrationFingerprintProviderInterface
{
    public function __construct(
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly EnvironmentReaderInterface $environmentReader,
    ) {
    }

    public static function supports(string $profileName): bool
    {
        return $profileName === Shopware6MajorProfile::PROFILE_NAME;
    }

    /**
     * @param array<string, mixed>|null $credentialFields
     */
    public function provide(?array $credentialFields, SwagMigrationConnectionEntity $connection): ?string
    {
        if ($credentialFields === null) {
            return null;
        }

        $connection->setCredentialFields($credentialFields);
        $migrationContext = $this->migrationContextFactory->createByConnection($connection);

        $response = $this->environmentReader->read($migrationContext);

        return $response['environmentInformation']['shopIdV2'] ?? null;
    }
}
