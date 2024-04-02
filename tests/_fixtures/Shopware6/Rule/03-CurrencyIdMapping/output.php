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
            'type' => 'orContainer',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'position' => 0,
            'id' => 'b4c302b9c24d47e993871ba23bc07879',
        ],
        [
            'type' => 'andContainer',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => 'b4c302b9c24d47e993871ba23bc07879',
            'position' => 0,
            'id' => '6706f920b9734a63b61d9b1919938151',
        ],
        [
            'type' => 'orContainer',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => '6706f920b9734a63b61d9b1919938151',
            'position' => 2,
            'id' => 'caf34ae4c5b94efdacba3f0e2dbcb365',
        ],
        [
            'type' => 'currency',
            'ruleId' => 'd6f8f14c3a3b4a33afa750758d79dd54',
            'parentId' => 'caf34ae4c5b94efdacba3f0e2dbcb365',
            'value' => [
                'operator' => '=',
                'currencyIds' => [
                    0 => 'a8d2554b0ce847cd82f3ac9bd1c0dfca',
                    1 => '18b930393f8b41bd92bb7ca3e8ef5429',
                    2 => '20b930393f8b41bd92bb7ca3e8ef5429',
                ],
            ],
            'position' => 0,
            'id' => '9f49cb90c31646f991b76d1b186736a5',
        ],
    ],
    'id' => 'd6f8f14c3a3b4a33afa750758d79dd54',
];
