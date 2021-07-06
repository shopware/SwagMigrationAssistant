<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Defaults;

return [
    'typeId' => '8a243080f92e4c719546314b577cf82b',
    'languageId' => '24f78d24f14849f6922ab2f76dcd76e8',
    'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
    'paymentMethodId' => 'c5917da8076b495ba80b14a61afd90fb',
    'shippingMethodId' => '6e028d0f24114544ad25235d56bb8846',
    'countryId' => '580a3bdd1739487f99ddd56ec365828f',
    'navigationCategoryId' => '7515fecfbfe14fbb87892873dbd1134d',
    'navigationCategoryDepth' => 2,
    'footerCategoryId' => '77b959cf66de4c1590c7f9b7da3982f3',
    'serviceCategoryId' => '19ca405790ff4f07aac8c599d4317868',
    'name' => 'Test',
    'accessKey' => 'SWSCA83PBVT8UA_X_P5UF6IJKG',
    'currencies' => [
        [
            'isoCode' => 'GBP',
            'factor' => 0.89157,
            'symbol' => '£',
            'shortName' => 'GBP',
            'name' => 'Pound',
            'position' => 1,
            'decimalPrecision' => 2,
            'isSystemDefault' => false,
            'id' => '18b930393f8b41bd92bb7ca3e8ef5429',
        ],
        [
            'isoCode' => 'EUR',
            'factor' => 1,
            'symbol' => '€',
            'shortName' => 'EUR',
            'name' => 'Euro',
            'position' => 1,
            'decimalPrecision' => 2,
            'isSystemDefault' => true,
            'id' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
        ],
    ],
    'languages' => [
        [
            'localeId' => '09f1b219723a4eef8522d001ed7709c3',
            'translationCodeId' => '09f1b219723a4eef8522d001ed7709c3',
            'name' => 'Deutsch',
            'id' => '24f78d24f14849f6922ab2f76dcd76e8',
        ],
        [
            'localeId' => '2356d13147454131928703d5f807aea1',
            'translationCodeId' => '2356d13147454131928703d5f807aea1',
            'name' => 'Polish',
            'id' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
        ],
    ],
    'active' => true,
    'maintenance' => false,
    'maintenanceIpWhitelist' => ['127.0.0.1'],
    'taxCalculationType' => 'horizontal',
    'countries' => [
        [
            'name' => 'Germany',
            'iso' => 'DE',
            'position' => 1,
            'taxFree' => false,
            'active' => true,
            'shippingAvailable' => true,
            'iso3' => 'DEU',
            'displayStateInRegistration' => false,
            'forceStateInRegistration' => false,
            'id' => '580a3bdd1739487f99ddd56ec365828f',
        ],
        [
            'name' => 'Poland',
            'iso' => 'PL',
            'position' => 10,
            'taxFree' => false,
            'active' => true,
            'shippingAvailable' => true,
            'iso3' => 'POL',
            'displayStateInRegistration' => false,
            'forceStateInRegistration' => false,
            'id' => '972a1788172c432db0eb6e2517c92e5e',
        ],
    ],
    'shippingMethods' => [
        [
            'name' => 'Standard',
            'active' => true,
            'deliveryTimeId' => '80293267cbeb4513bc4d3cede0144022',
            'availabilityRuleId' => '28caae75a5624f0d985abd0eb32aa160',
            'id' => '6e028d0f24114544ad25235d56bb8846',
        ],
    ],
    'translations' => [
        [
            'salesChannelId' => '3f05f1f6514d43f7ae11a669c7557d1c',
            'name' => 'Test',
            'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
        ],
    ],
    'customerGroupId' => 'cfbd5018d38d41d8adca10d94fc8bdd6',
    'paymentMethodIds' => ['c5917da8076b495ba80b14a61afd90fb'],
    'hreflangActive' => true,
    'id' => Defaults::SALES_CHANNEL,
];
