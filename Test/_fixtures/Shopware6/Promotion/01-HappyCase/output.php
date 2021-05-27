<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'name' => 'BlackFriday',
    'active' => true,
    'validFrom' => '2020-11-01T12:00:00.000+00:00',
    'validUntil' => '2020-12-31T12:00:00.000+00:00',
    'maxRedemptionsGlobal' => 1500,
    'maxRedemptionsPerCustomer' => 5,
    'exclusive' => false,
    'useCodes' => true,
    'useSetGroups' => false,
    'customerRestriction' => false,
    'useIndividualCodes' => true,
    'individualCodePattern' => 'BLACK-FRIDAY-%d%d',
    'salesChannels' => [
        [
            'salesChannelId' => '98432def39fc4624b33213a56b8c944d',
            'priority' => 1,
            'id' => '0eb744c71b88478f81ece1be37b7b846',
        ],
        [
            'salesChannelId' => 'cd2fc096384c4e048791f6aef41af84c',
            'priority' => 1,
            'id' => '304b7c825c96498e847a6318badb8340',
        ],
        [
            'salesChannelId' => '11d1d4e0218e4eb48e1a1eceac9c76d3',
            'priority' => 1,
            'id' => '6fdf440b227148b7824234ed9da33658',
        ],
    ],
    'discounts' => [
        [
            'scope' => 'cart',
            'type' => 'percentage',
            'value' => 10,
            'considerAdvancedRules' => false,
            'maxValue' => 1000,
            'sorterKey' => 'PRICE_ASC',
            'applierKey' => 'ALL',
            'usageKey' => 'ALL',
            'id' => 'eb25d17b770645e3baa7dac2a6dc60ab',
        ],
    ],
    'individualCodes' => [
        [
            'code' => 'BLACK-FRIDAY-33',
            'id' => '0078ec1575554ddb8f80fb8128bc5a6f',
        ],
        [
            'code' => 'BLACK-FRIDAY-71',
            'payload' => [
                'orderId' => '96d7123158bb4fb28cb5e2342d1fa38b',
                'customerId' => '828e0550c1b3402e89f7757b0090d4b4',
                'customerName' => 'Krispin Luetjann',
            ],
            'id' => '2e57d9d01e2848ea9475f644fd76647c',
        ],
        [
            'code' => 'BLACK-FRIDAY-42',
            'id' => '331fb687cf42470bbf0223b8dc7df16c',
        ],
        [
            'code' => 'BLACK-FRIDAY-0',
            'payload' => [
                'orderId' => 'e2afd5d90f1848b5b720ab23245d4712',
                'customerId' => '828e0550c1b3402e89f7757b0090d4b4',
                'customerName' => 'Krispin Luetjann',
            ],
            'id' => '3e2a612b96d94c88af5be2a4bda66759',
        ],
        [
            'code' => 'BLACK-FRIDAY-78',
            'id' => '6246f2dbaaed492f9fb742fcba514a73',
        ],
        [
            'code' => 'BLACK-FRIDAY-95',
            'id' => '686872885bc0430cb25c4b503042bbbd',
        ],
        [
            'code' => 'BLACK-FRIDAY-20',
            'id' => '88520e2c71e54edfa35c4e5ee11f35b2',
        ],
        [
            'code' => 'BLACK-FRIDAY-23',
            'id' => 'a5afda08b58648b9a44bb99f1368ba77',
        ],
        [
            'code' => 'BLACK-FRIDAY-93',
            'id' => 'b3b28b67f79f43a98d87aa669ebd3c3a',
        ],
        [
            'code' => 'BLACK-FRIDAY-14',
            'id' => 'b7b80913f3654595a381ed20e3a68b54',
        ],
        [
            'code' => 'BLACK-FRIDAY-50',
            'id' => 'be4189635e2c403f8798686253373f4a',
        ],
        [
            'code' => 'BLACK-FRIDAY-39',
            'id' => 'eccb8ea3707c43b2b1d3d04b90c9f765',
        ],
    ],
    'personaRules' => [
        [
            'id' => '8b773a602272453eb4df291c7c83c5e5',
        ],
    ],
    'personaCustomers' => [
        [
            'id' => '828e0550c1b3402e89f7757b0090d4b4',
        ],
    ],
    'cartRules' => [
        [
            'id' => '8b773a602272453eb4df291c7c83c5e5',
        ],
    ],
    'translations' => [
        [
            'name' => 'BlackFriday',
            'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
        ],
    ],
    'orderCount' => 2,
    'ordersPerCustomerCount' => [
        '828e0550c1b3402e89f7757b0090d4b4' => 2,
    ],
    'exclusionIds' => [
        0 => 'cb88e0d0bde9419baaa09ed0508c50b9',
    ],
    'id' => '7e8b76efced84b6ab4d9737a580ef8c8',
];
