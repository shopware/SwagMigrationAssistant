<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'name' => 'CoolerRuler',
    'priority' => 100,
    'moduleTypes' => [
        'types' => [
            0 => 'price',
        ],
    ],
    'conditions' => [
        [
            'type' => 'andContainer',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => 'b4c302b9c24d47e993871ba23bc07879',
            'position' => 0,
            'id' => '6706f920b9734a63b61d9b1919938151',
        ],
        [
            'type' => 'customerBillingCountry',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => '6706f920b9734a63b61d9b1919938151',
            'value' => [
                'operator' => '=',
                'countryIds' => [
                    0 => '0585f3e267b54f539a99131dd2f26153',
                    1 => '055696482bbc4054b219fda48d96b890',
                    2 => '0388b417e55d4aacb4f8a976f9afe1a2',
                ],
            ],
            'position' => 1,
            'id' => 'a9e706e1f4f146aab878db69d1b168f1',
        ],
        [
            'type' => 'orContainer',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => '6706f920b9734a63b61d9b1919938151',
            'position' => 2,
            'id' => 'caf34ae4c5b94efdacba3f0e2dbcb365',
        ],
        [
            'type' => 'customerCustomerGroup',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => 'caf34ae4c5b94efdacba3f0e2dbcb365',
            'value' => [
                'operator' => '=',
                'customerGroupIds' => [
                    0 => '112253b96f604f9ba7245905feba0619',
                    1 => 'cfbd5018d38d41d8adca10d94fc8bdd6',
                ],
            ],
            'position' => 1,
            'id' => '14d5aa6bdc70492a9ad32c3dc139ac43',
        ],
        [
            'type' => 'currency',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => 'caf34ae4c5b94efdacba3f0e2dbcb365',
            'value' => [
                'operator' => '=',
                'currencyIds' => [
                    0 => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                    1 => '361d6f374fe843cba4ad2b6ceccd476c',
                    2 => '71195ff815624cb08637f30f26a9379b',
                ],
            ],
            'position' => 0,
            'id' => '9f49cb90c31646f991b76d1b186736a5',
        ],
        [
            'type' => 'customerOrderCount',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => '6706f920b9734a63b61d9b1919938151',
            'value' => [
                'operator' => '=',
                'count' => 150,
            ],
            'position' => 0,
            'id' => 'ba6fbc67f6d448d5b2427f6e6760c872',
        ],
        [
            'type' => 'orContainer',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'position' => 0,
            'id' => 'b4c302b9c24d47e993871ba23bc07879',
        ],
    ],
    'id' => 'd6f8f14c3a3b4a33afa750758d79dd54',
];
