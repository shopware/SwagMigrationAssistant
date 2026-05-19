<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\Helper\ConnectionNameSanitizer;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertDateTimeFailedLog;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Premapping\TimezoneReader;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
abstract class ShopwareConverter extends Converter implements ResetInterface
{
    protected const TYPE_STRING = 'string';
    protected const TYPE_BOOLEAN = 'bool';
    protected const TYPE_INVERT_BOOLEAN = 'invert_bool';
    protected const TYPE_INTEGER = 'int';
    protected const TYPE_FLOAT = 'float';
    protected const TYPE_DATE = 'date';
    protected const TYPE_DATETIME = 'datetime';

    protected MigrationContextInterface $migrationContext;

    /**
     * @var array<string, string|null>
     */
    private array $timezoneCache = [];

    public function reset(): void
    {
        $this->timezoneCache = [];
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function resolveLanguageId(string $locale, LanguageLookup $languageLookup, Context $context): ?string
    {
        $mapping = $this->mappingService->getMapping(
            $this->migrationContext->getConnection()->getId(),
            DefaultEntities::LANGUAGE,
            $locale,
            $context
        );

        if (isset($mapping['entityId'])) {
            $this->mappingIds[] = $mapping['id'];

            return $mapping['entityId'];
        }

        return $languageLookup->get($locale, $context);
    }

    /**
     * @param array<string, mixed> $newData
     * @param array<string, mixed> $sourceData
     */
    protected function convertValue(
        array &$newData,
        string $newKey,
        array &$sourceData,
        string $sourceKey,
        string $castType = self::TYPE_STRING,
    ): void {
        if (isset($sourceData[$sourceKey]) && $sourceData[$sourceKey] !== '') {
            switch ($castType) {
                case self::TYPE_BOOLEAN:
                    $sourceValue = (bool) $sourceData[$sourceKey];

                    break;
                case self::TYPE_INVERT_BOOLEAN:
                    $sourceValue = !(bool) $sourceData[$sourceKey];

                    break;
                case self::TYPE_INTEGER:
                    $sourceValue = (int) $sourceData[$sourceKey];

                    break;
                case self::TYPE_FLOAT:
                    $sourceValue = (float) $sourceData[$sourceKey];

                    break;
                case self::TYPE_DATE:
                    $sourceValue = $sourceData[$sourceKey];
                    if (!$this->validDate($sourceValue)) {
                        return;
                    }

                    break;
                case self::TYPE_DATETIME:
                    $dataset = $this->migrationContext->getDataSet();
                    $entityName = null;
                    if ($dataset instanceof DataSet) {
                        $entityName = $dataset::getEntity();
                    }

                    $sourceValue = $this->convertDateTime((string) $sourceData[$sourceKey], $entityName);

                    if ($sourceValue === null) {
                        return;
                    }

                    break;
                default:
                    $sourceValue = (string) $sourceData[$sourceKey];
            }
            $newData[$newKey] = $sourceValue;
        }
        unset($sourceData[$sourceKey]);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param list<string> $excludeList
     *
     * @return array<string, mixed>|null
     */
    protected function getAttributes(
        array $attributes,
        string $entityName,
        string $connectionName,
        array $excludeList = [],
        ?Context $context = null,
    ): ?array {
        $result = [];

        $connectionName = ConnectionNameSanitizer::sanitize($connectionName);

        foreach ($attributes as $attribute => $value) {
            if (\in_array($attribute, $excludeList, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $connection = $this->migrationContext->getConnection();
            if ($context !== null) {
                $connectionId = $connection->getId();
                $mapping = $this->mappingService->getMapping(
                    $connectionId,
                    $entityName . '_custom_field',
                    $attribute,
                    $context
                );

                if ($mapping !== null) {
                    $this->mappingIds[] = $mapping['id'];

                    if (isset($mapping['additionalData']['columnType'])
                        && \in_array($mapping['additionalData']['columnType'], ['text', 'string'], true)
                        && $value !== \strip_tags($value)
                    ) {
                        continue;
                    }

                    if (isset($mapping['additionalData']['columnType']) && $mapping['additionalData']['columnType'] === 'boolean') {
                        $value = (bool) $value;
                    }

                    if (isset($mapping['additionalData']['columnType']) && $mapping['additionalData']['columnType'] === 'integer') {
                        $value = (int) $value;
                    }

                    if (isset($mapping['additionalData']['columnType']) && $mapping['additionalData']['columnType'] === 'float') {
                        $value = (float) $value;
                    }

                    if (isset($mapping['additionalData']['columnType']) && $mapping['additionalData']['columnType'] === 'datetime') {
                        $convertedValue = $this->convertDateTime((string) $value, $entityName);

                        if ($convertedValue === null) {
                            continue;
                        }

                        $value = $convertedValue;
                    }
                }
            }

            $result['migration_' . $connectionName . '_' . $entityName . '_' . $attribute] = $value;
        }

        if ($result === []) {
            return null;
        }

        return $result;
    }

    protected function validDate(string $value): bool
    {
        try {
            new \DateTime($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function convertDateTime(string $value, ?string $entityName): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            $timezone = $this->getTimezoneFromPremapping();
            if ($timezone === null) {
                return (new \DateTimeImmutable($value))->format(Defaults::STORAGE_DATE_TIME_FORMAT);
            }

            $date = new \DateTimeImmutable($value, new \DateTimeZone($timezone));

            return $date
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        } catch (\Throwable $exception) {
            $logBuilder = MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                ->withSourceData(['dateTime' => $value])
                ->withExceptionMessage($exception->getMessage())
                ->withException($exception);

            if ($entityName !== null) {
                $logBuilder->withEntityName($entityName);
            }

            $this->loggingService->log($logBuilder->build(ConvertDateTimeFailedLog::class));

            return null;
        }
    }

    /**
     * We do not want to add an optional context to the
     * "ShopwareConverter::convertValue()" method, as this would break the API
     *
     * That is why:
     * the timezone is read from the connection premapping because the converter has
     * no Shopware "Context" available.
     *
     * "MappingServiceInterface::getMapping()" requires a "Context".
     * The timezone is a premapping configuration, so reading it from
     * "$migrationContext->getConnection()->getPremapping()" keeps it available
     * during conversion without DAL.
     */
    private function getTimezoneFromPremapping(): ?string
    {
        $runId = $this->migrationContext->getRunUuid();
        if (\array_key_exists($runId, $this->timezoneCache)) {
            return $this->timezoneCache[$runId];
        }

        $timezone = null;
        $premapping = $this->migrationContext->getConnection()->getPremapping();
        foreach ($premapping ?? [] as $item) {
            if ($item->getEntity() !== TimezoneReader::MAPPING_NAME) {
                continue;
            }

            foreach ($item->getMapping() as $mapping) {
                if ($mapping->getSourceId() === TimezoneReader::SOURCE_ID) {
                    $timezone = $mapping->getDestinationUuid() === '' ? null : $mapping->getDestinationUuid();
                }
            }
        }

        $this->timezoneCache[$runId] = $timezone;

        return $timezone;
    }
}
