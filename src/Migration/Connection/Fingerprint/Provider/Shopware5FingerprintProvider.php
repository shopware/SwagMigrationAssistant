<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader as ApiEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader as LocalEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;

#[Package('fundamentals@after-sales')]
class Shopware5FingerprintProvider implements MigrationFingerprintProviderInterface
{
    public function __construct(
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly ApiEnvironmentReader $apiEnvironmentReader,
        private readonly LocalEnvironmentReader $localEnvironmentReader,
    ) {
    }

    public static function supports(string $profileName): bool
    {
        return \in_array(
            $profileName,
            [
                Shopware54Profile::PROFILE_NAME,
                Shopware55Profile::PROFILE_NAME,
                Shopware56Profile::PROFILE_NAME,
                Shopware57Profile::PROFILE_NAME,
            ],
            true
        );
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

        $environmentReader = $this->getEnvironmentReader($connection->getGatewayName());

        if ($environmentReader === null) {
            return null;
        }

        $response = $environmentReader->read($migrationContext);
        $data = $this->extractData($response);

        if ($data === null) {
            return null;
        }

        return Hasher::hash($data['esdKey'] . $data['installationDate']);
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array{esdKey: string, installationDate: string}|null
     */
    private function extractData(array $response): ?array
    {
        $config = null;

        if (\array_key_exists('config', $response)) {
            $config = $response['config'];
        }

        if (\array_key_exists('environmentInformation', $response)) {
            $config = $response['environmentInformation']['config'] ?? null;
        }

        if ($config === null) {
            return null;
        }

        return [
            'esdKey' => $config['esdKey'],
            'installationDate' => $config['installationDate'],
        ];
    }

    private function getEnvironmentReader(string $gatewayName): ?EnvironmentReaderInterface
    {
        if ($gatewayName === ShopwareApiGateway::GATEWAY_NAME) {
            return $this->apiEnvironmentReader;
        }

        if ($gatewayName === ShopwareLocalGateway::GATEWAY_NAME) {
            return $this->localEnvironmentReader;
        }

        return null;
    }
}
