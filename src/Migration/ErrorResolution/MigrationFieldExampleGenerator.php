<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\ErrorResolution;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CashRoundingConfigField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceDefinitionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TaxFreeConfigField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VariantListingConfigField;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
readonly class MigrationFieldExampleGenerator
{
    public static function generateExample(Field $field): ?string
    {
        $example = self::buildExample($field);

        if ($example === null) {
            return null;
        }

        $encoded = \json_encode($example, \JSON_PRETTY_PRINT);

        if ($encoded === false) {
            return null;
        }

        return $encoded;
    }

    public static function getFieldType(Field $field): string
    {
        return (new \ReflectionClass($field))->getShortName();
    }

    private static function buildExample(Field $field): mixed
    {
        $specialExample = self::getSpecialFieldExample($field);

        if ($specialExample !== null) {
            return $specialExample;
        }

        if ($field instanceof CustomFields || $field instanceof ObjectField) {
            return null;
        }

        if ($field instanceof ListField) {
            $fieldType = $field->getFieldType();

            if ($fieldType === null) {
                return [];
            }

            /** @var Field $elementField */
            $elementField = new $fieldType('example', 'example');
            $elementExample = self::buildExample($elementField);

            return $elementExample !== null ? [$elementExample] : [];
        }

        if ($field instanceof JsonField) {
            if (empty($field->getPropertyMapping())) {
                return [];
            }

            return self::buildFromPropertyMapping($field->getPropertyMapping());
        }

        return self::getScalarDefault($field);
    }

    /**
     * @param list<Field> $fields
     *
     * @return array<string, mixed>
     */
    private static function buildFromPropertyMapping(array $fields): array
    {
        $result = [];

        foreach ($fields as $nestedField) {
            $result[$nestedField->getPropertyName()] = self::buildExample($nestedField);
        }

        return $result;
    }

    private static function getScalarDefault(Field $field): mixed
    {
        return match (true) {
            $field instanceof IntField => 0,
            $field instanceof FloatField => 0.1,
            $field instanceof BoolField => false,
            $field instanceof StringField, $field instanceof TranslatedField => '[string]',
            $field instanceof IdField, $field instanceof FkField => '[uuid]',
            $field instanceof DateField => \sprintf('[date (%s)]', Defaults::STORAGE_DATE_FORMAT),
            $field instanceof DateTimeField => \sprintf('[datetime (%s)]', Defaults::STORAGE_DATE_TIME_FORMAT),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private static function getSpecialFieldExample(Field $field): ?array
    {
        return match (true) {
            $field instanceof PriceField => [
                [
                    'currencyId' => '[uuid]',
                    'gross' => 0.1,
                    'net' => 0.1,
                    'linked' => false,
                ],
            ],
            $field instanceof VariantListingConfigField => [
                'displayParent' => false,
                'mainVariantId' => '[uuid]',
                'configuratorGroupConfig' => [],
            ],
            $field instanceof PriceDefinitionField => [
                'type' => 'quantity',
                'price' => 0.1,
                'quantity' => 1,
                'isCalculated' => false,
                'taxRules' => [
                    [
                        'taxRate' => 0.1,
                        'percentage' => 0.1,
                    ],
                ],
            ],
            $field instanceof CartPriceField => [
                'netPrice' => 0.1,
                'totalPrice' => 0.1,
                'positionPrice' => 0.1,
                'rawTotal' => 0.1,
                'taxStatus' => 'gross',
                'calculatedTaxes' => [
                    [
                        'tax' => 0.1,
                        'taxRate' => 0.1,
                        'price' => 0.1,
                    ],
                ],
                'taxRules' => [
                    [
                        'taxRate' => 0.1,
                        'percentage' => 0.1,
                    ],
                ],
            ],
            $field instanceof CalculatedPriceField => [
                'unitPrice' => 0.1,
                'totalPrice' => 0.1,
                'quantity' => 1,
                'calculatedTaxes' => [
                    [
                        'tax' => 0.1,
                        'taxRate' => 0.1,
                        'price' => 0.1,
                    ],
                ],
                'taxRules' => [
                    [
                        'taxRate' => 0.1,
                        'percentage' => 0.1,
                    ],
                ],
            ],
            $field instanceof CashRoundingConfigField => [
                'decimals' => 2,
                'interval' => 0.01,
                'roundForNet' => true,
            ],
            $field instanceof TaxFreeConfigField => [
                'enabled' => false,
                'currencyId' => '[uuid]',
                'amount' => 0.1,
            ],
            default => null,
        };
    }
}
