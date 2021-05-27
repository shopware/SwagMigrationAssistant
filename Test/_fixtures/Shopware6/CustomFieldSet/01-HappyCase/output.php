<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => '48cde772961d446480fdbb32f4913319',
    'name' => 'custom_Electronics',
    'config' => [
        'label' => [
            'en-GB' => 'Electronics',
        ],
    ],
    'active' => true,
    'global' => false,
    'position' => 1,
    'customFields' => [
        [
            'name' => 'custom_electronics_aliquid_non_vel',
            'type' => 'datetime',
            'config' => [
                'componentName' => 'sw-field',
                'type' => 'date',
                'dateType' => 'datetime',
                'customFieldType' => 'date',
                'label' => [
                    'en-GB' => 'aliquid non vel',
                ],
                'customFieldPosition' => 1,
            ],
            'active' => true,
            'customFieldSetId' => '48cde772961d446480fdbb32f4913319',
            'id' => 'b1a7ffea4e674553a2ff1988e758591a',
        ],
    ],
    'relations' => [
        [
            'entityName' => 'customer',
            'customFieldSetId' => '48cde772961d446480fdbb32f4913319',
            'id' => '1b5deaf06caf48038f2a3c8fbe5c8f9a',
        ],
        [
            'entityName' => 'product',
            'customFieldSetId' => '48cde772961d446480fdbb32f4913319',
            'id' => '2cc291fd438e4ba8b514399e228a8257',
        ],
        [
            'entityName' => 'order',
            'customFieldSetId' => '48cde772961d446480fdbb32f4913319',
            'id' => '45016ac73773457195f594ee57f544fc',
        ],
        [
            'entityName' => 'media',
            'customFieldSetId' => '48cde772961d446480fdbb32f4913319',
            'id' => '93bb988f105d47718aa3925b4154754c',
        ],
        [
            'entityName' => 'product_manufacturer',
            'customFieldSetId' => '48cde772961d446480fdbb32f4913319',
            'id' => 'f4a696db84c249adbbd722fd67974ce1',
        ],
    ],
];
