<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Helper;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;

#[Package('fundamentals@after-sales')]
class ConnectionFingerprintService
{
    public const FIELD_KEY_ENDPOINT = 'endpoint';

    public const FIELD_KEY_HOST = 'dbHost';

    public const FIELD_KEY_PORT = 'dbPort';

    public const FIELD_KEY_NAME = 'dbName';

    public const FIELD_KEY_PROFILE = 'profile';

    public const DEFAULT_DB_PORT = '3306';

    /**
     * @param EntityRepository<SwagMigrationConnectionCollection> $connectionRepo
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $connectionRepo,
    ) {
    }

    /**
     * @param array<string, mixed>|null $credentialFields
     */
    public function generateFingerprint(?array $credentialFields, string $gatewayName, string $profileName): ?string
    {
        if (empty($credentialFields)) {
            return null;
        }

        $data = $this->extractFingerprintData($credentialFields, $gatewayName);

        if (empty($data)) {
            return null;
        }

        $data[self::FIELD_KEY_PROFILE] = $profileName;
        ksort($data);

        return Hasher::hash($data);
    }

    public function hasDuplicateConnection(string $fingerprint, Context $context, ?string $excludeConnectionId): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sourceSystemFingerprint', $fingerprint));

        if (isset($excludeConnectionId)) {
            $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('id', $excludeConnectionId),
            ]));
        }

        return $this->connectionRepo->search($criteria, $context)->getTotal() > 0;
    }

    /**
     * @param array<string, mixed> $credentialFields
     *
     * @return array<string, string>|null
     */
    private function extractFingerprintData(array $credentialFields, string $gatewayName): ?array
    {
        if ($gatewayName === ShopwareApiGateway::GATEWAY_NAME) {
            return $this->extractApiFingerprintData($credentialFields, $gatewayName);
        }

        if ($gatewayName === ShopwareLocalGateway::GATEWAY_NAME) {
            return $this->extractLocalFingerprintData($credentialFields);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $credentialFields
     *
     * @return array<string, string>|null
     */
    private function extractApiFingerprintData(array $credentialFields, string $gatewayName): ?array
    {
        if (!isset($credentialFields[self::FIELD_KEY_ENDPOINT])) {
            return null;
        }

        $normalizedEndpoint = $this->normalizeEndpoint(
            (string) $credentialFields[self::FIELD_KEY_ENDPOINT]
        );

        return [
            'type' => $gatewayName,
            self::FIELD_KEY_ENDPOINT => $normalizedEndpoint,
        ];
    }

    /**
     * @param array<string, mixed> $credentialFields
     *
     * @return array<string, string>|null
     */
    private function extractLocalFingerprintData(array $credentialFields): ?array
    {
        if (!isset($credentialFields[self::FIELD_KEY_HOST], $credentialFields[self::FIELD_KEY_NAME])) {
            return null;
        }

        return [
            'type' => ShopwareLocalGateway::GATEWAY_NAME,
            self::FIELD_KEY_HOST => \strtolower(\trim((string) $credentialFields[self::FIELD_KEY_HOST])),
            self::FIELD_KEY_PORT => (string) ($credentialFields[self::FIELD_KEY_PORT] ?? self::DEFAULT_DB_PORT),
            self::FIELD_KEY_NAME => \trim((string) $credentialFields[self::FIELD_KEY_NAME]),
        ];
    }

    private function normalizeEndpoint(string $endpoint): string
    {
        // lowercase & trim
        $endpoint = strtolower(trim($endpoint));

        // remove ending slash
        $endpoint = rtrim($endpoint, '/');

        // remove protocol (http or https does not matter for fingerprint)
        $endpoint = (string) preg_replace('#^https?://#', '', $endpoint);

        // remove www. prefix
        return (string) preg_replace('#^www\.#', '', $endpoint);
    }
}
