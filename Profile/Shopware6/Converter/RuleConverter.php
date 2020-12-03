<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class RuleConverter extends ShopwareConverter
{
    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::RULE,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['conditions'])) {
            foreach ($converted['conditions'] as &$condition) {
                if (isset($condition['type']) && $condition['type'] === 'alwaysValid') {
                    unset($condition['value']);
                }

                if (isset($condition['type']) && $condition['type'] === 'customerIsCompany' && !isset($condition['value'])) {
                    $condition['value'] = [
                        'isCompany' => false,
                    ];
                }

                if (isset($condition['type']) && $condition['type'] === 'customerIsNewCustomer' && !isset($condition['value'])) {
                    $condition['value'] = [
                        'isNew' => false,
                    ];
                }

                if (isset($condition['type'], $condition['value']['currencyIds']) && $condition['type'] === 'currency') {
                    $newCurrencies = [];
                    $currencyIds = $condition['value']['currencyIds'];
                    foreach ($currencyIds as $currencyId) {
                        $uuid = $this->getMappingIdFacade(DefaultEntities::CURRENCY, $currencyId);
                        if ($uuid !== null) {
                            $newCurrencies[] = $uuid;
                        }
                    }

                    if (!empty($newCurrencies)) {
                        $condition['value']['currencyIds'] = $newCurrencies;
                    }
                }

                if (isset($condition['type'], $condition['value']['countryIds']) && ($condition['type'] === 'customerBillingCountry' || $condition['type'] === 'customerShippingCountry')) {
                    $newCurrencies = [];
                    $countryIds = $condition['value']['countryIds'];
                    foreach ($countryIds as $countryId) {
                        $uuid = $this->getMappingIdFacade(DefaultEntities::COUNTRY, $countryId);
                        if ($uuid !== null) {
                            $newCurrencies[] = $uuid;
                        }
                    }

                    if (!empty($newCurrencies)) {
                        $condition['value']['countryIds'] = $newCurrencies;
                    }
                }
            }
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
