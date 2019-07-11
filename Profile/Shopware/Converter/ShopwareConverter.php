<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use SwagMigrationAssistant\Migration\Converter\ConverterInterface;

abstract class ShopwareConverter implements ConverterInterface
{
    protected const TYPE_STRING = 'string';
    protected const TYPE_BOOLEAN = 'bool';
    protected const TYPE_INTEGER = 'int';
    protected const TYPE_FLOAT = 'float';
    protected const TYPE_DATETIME = 'datetime';

    protected function convertValue(
        array &$newData,
        string $newKey,
        array &$sourceData,
        string $sourceKey,
        string $castType = self::TYPE_STRING
    ): void {
        if ($sourceData[$sourceKey] !== null && $sourceData[$sourceKey] !== '') {
            switch ($castType) {
                case self::TYPE_BOOLEAN:
                    $sourceValue = (bool) $sourceData[$sourceKey];
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
     * @param string[] $requiredDataFieldKeys
     *
     * @return string[]
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

    protected function validDate(string $value): bool
    {
        try {
            new \DateTime($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
