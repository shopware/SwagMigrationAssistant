<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TimezoneReader as ApiTimezoneReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('after-sales')]
class TimezoneReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'source_timezone';

    private const SOURCE_ID = 'timezone';

    /**
     * @var array<string, int>
     */
    private array $validTimezones;

    public function __construct(
        private readonly ApiTimezoneReader $timezoneReader,
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

        $choices = $this->getTimeZoneList();

        $sourceTimezone = $this->readSourceTimezone($migrationContext);

        $description = $sourceTimezone ?? 'No source time zone';
        $destinationTimezone = '';

        if ($sourceTimezone !== null && isset($this->validTimezones[$sourceTimezone])) {
            $destinationTimezone = $sourceTimezone;
        }

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
                    $description,
                    $destinationTimezone
                ),
            ],
            $choices
        );
    }

    private function readSourceTimezone(MigrationContextInterface $migrationContext): ?string
    {
        if ($migrationContext->getConnection()->getGatewayName() !== ShopwareApiGateway::GATEWAY_NAME) {
            return null;
        }

        $timezoneResult = $this->timezoneReader->read($migrationContext);
        $timezone = $timezoneResult[0]['timezone'] ?? null;
        if (!\is_string($timezone) || $timezone === '' || !isset($this->validTimezones[$timezone])) {
            return null;
        }

        return $timezone;
    }

    /**
     * @return array<PremappingChoiceStruct>
     */
    private function getTimeZoneList(): array
    {
        return array_map(
            static fn (string $timezone): PremappingChoiceStruct => new PremappingChoiceStruct($timezone, $timezone),
            \DateTimeZone::listIdentifiers()
        );
    }
}
