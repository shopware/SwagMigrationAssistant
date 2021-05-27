<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => 'caa06cdb89c64fa6ad18a738c1a8edaf',
    'name' => 'Customer is Company',
    'priority' => 100,
    'moduleTypes' => [
        'types' => [
            0 => 'shipping',
            1 => 'payment',
            2 => 'price',
        ],
    ],
    'conditions' => [
        [
            'type' => 'orContainer',
            'ruleId' => 'caa06cdb89c64fa6ad18a738c1a8edaf',
            'position' => 0,
            'id' => '482fb3e675104427b4aa8d91dc8f0f02',
        ],
        [
            'type' => 'andContainer',
            'ruleId' => 'caa06cdb89c64fa6ad18a738c1a8edaf',
            'parentId' => '482fb3e675104427b4aa8d91dc8f0f02',
            'position' => 0,
            'id' => 'e005f3b77f1a4bfa98ba0b4f91c27074',
        ],
        [
            'type' => 'customerIsCompany',
            'ruleId' => 'caa06cdb89c64fa6ad18a738c1a8edaf',
            'parentId' => 'e005f3b77f1a4bfa98ba0b4f91c27074',
            'position' => 0,
            'id' => '9f8b56b08f2a4bf3a4b41210811414c8',
        ],
    ],
    'invalid' => false,
];
