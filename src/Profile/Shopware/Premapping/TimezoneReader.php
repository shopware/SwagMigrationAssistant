<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TimezoneReader as ApiTimezoneReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TimezoneReader as LocalTimezoneReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class TimezoneReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'source_timezone';

    private const SOURCE_ID = 'timezone';

    private array $validTimezones;

    public function __construct(
        private readonly ApiTimezoneReader $apiTimezoneReader,
        private readonly LocalTimezoneReader $localTimezoneReader,
    ) {
        $this->validTimezones = \array_flip(\DateTimeZone::listIdentifiers());
    }

    public function supports(MigrationContextInterface $migrationContext, array $entityGroupNames): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && \in_array($migrationContext->getGateway()->getName(), [
                ShopwareApiGateway::GATEWAY_NAME,
                ShopwareLocalGateway::GATEWAY_NAME,
            ], true);
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function getPremapping(Context $context, MigrationContextInterface $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingDictionary($migrationContext);

        $choices = $this->getChoices();

        $sourceTimezone = $this->readSourceTimezone($migrationContext);

        if ($sourceTimezone === null || !isset($this->validTimezones[$sourceTimezone])) {
            return new PremappingStruct(self::getMappingName(), [], $choices);
        }

        $destinationTimezone = $sourceTimezone;

        if (isset($this->connectionPremappingDictionary[self::SOURCE_ID])) {
            $configuredDestination = $this->connectionPremappingDictionary[self::SOURCE_ID]->getDestinationUuid();

            if (isset($this->validTimezones[$configuredDestination])) {
                $destinationTimezone = $configuredDestination;
            }
        }

        return new PremappingStruct(
            self::getMappingName(),
            [
                new PremappingEntityStruct(
                    self::SOURCE_ID,
                    $sourceTimezone,
                    $destinationTimezone
                ),
            ],
            $choices
        );
    }

    private function readSourceTimezone(MigrationContextInterface $migrationContext): ?string
    {
        $gateway = $this->getReader($migrationContext);
        if (!$gateway instanceof ShopwareGatewayInterface) {
            return null;
        }

        $result = $gateway->read($migrationContext);

        $timezone = $result[0]['timezone'] ?? null;

        if (!\is_string($timezone) || $timezone === '' || !isset($this->validTimezones[$timezone])) {
            return null;
        }

        return $timezone;
    }

    private function getChoices(): array
    {
        return array_map(
            static fn (string $timezone): PremappingChoiceStruct => new PremappingChoiceStruct($timezone, $timezone),
            \DateTimeZone::listIdentifiers()
        );
    }

    private function getReader(MigrationContextInterface $migrationContext): ReaderInterface
    {
        if ($this->apiTimezoneReader->supports($migrationContext)) {
            return $this->apiTimezoneReader;
        }

        return $this->localTimezoneReader;
    }
}
