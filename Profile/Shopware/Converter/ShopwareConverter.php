<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class ShopwareConverter extends Converter
{
    protected const TYPE_STRING = 'string';
    protected const TYPE_BOOLEAN = 'bool';
    protected const TYPE_INVERT_BOOLEAN = 'invert_bool';
    protected const TYPE_INTEGER = 'int';
    protected const TYPE_FLOAT = 'float';
    protected const TYPE_DATETIME = 'datetime';

    protected MigrationContextInterface $migrationContext;

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
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
        string $castType = self::TYPE_STRING
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
                case self::TYPE_DATETIME:
                    $sourceValue = $sourceData[$sourceKey];
                    if (!$this->validDate($sourceValue)) {
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
     * @param array<string, mixed> $rawData
     * @param list<string> $requiredDataFieldKeys
     *
     * @return list<string>
     */
    protected function checkForEmptyRequiredDataFields(array $rawData, array $requiredDataFieldKeys): array
    {
        $emptyFields = [];
        foreach ($requiredDataFieldKeys as $requiredDataFieldKey) {
            if (!isset($rawData[$requiredDataFieldKey]) || $rawData[$requiredDataFieldKey] === '') {
                $emptyFields[] = $requiredDataFieldKey;
            }
        }

        return $emptyFields;
    }

    /**
     * @param array<string, mixed> $converted
     * @param array<string, string> $requiredDataFields
     *
     * @return list<string>
     */
    protected function checkForEmptyRequiredConvertedFields(array $converted, array $requiredDataFields): array
    {
        $emptyFields = [];
        foreach ($requiredDataFields as $requiredDataFieldKey => $requiredDataFieldValue) {
            if (!isset($converted[$requiredDataFieldKey]) || $converted[$requiredDataFieldKey] === '') {
                $emptyFields[] = $requiredDataFieldValue;
            }
        }

        return $emptyFields;
    }

    protected function validDate(string $value): bool
    {
        try {
            new \DateTime($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
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
        ?Context $context = null
    ): ?array {
        $result = [];
        // remove unwanted characters from connection name
        $connectionName = \str_replace(' ', '', $connectionName);
        $connectionName = \preg_replace('/[^A-Za-z0-9\-]/', '', $connectionName);

        foreach ($attributes as $attribute => $value) {
            if (\in_array($attribute, $excludeList, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $connection = $this->migrationContext->getConnection();
            if ($context !== null && $connection !== null) {
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
                }
            }

            $result['migration_' . $connectionName . '_' . $entityName . '_' . $attribute] = $value;
        }

        if (empty($result)) {
            return null;
        }

        return $result;
    }
}
