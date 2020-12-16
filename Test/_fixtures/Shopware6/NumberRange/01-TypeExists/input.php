<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => '54e9798c12614a198c6b96d87412646d',
    'typeId' => 'bc7eb01d348e4546a7abe666f41e3648',
    'global' => true,
    'name' => 'Invoices',
    'description' => 'Description',
    'pattern' => '{n}',
    'start' => 1000,
    'type' => [
        'typeName' => 'Invoice',
        'technicalName' => 'document_invoice',
        'global' => false,
        'id' => 'bc7eb01d348e4546a7abe666f41e3648',
    ],
    'numberRangeSalesChannels' => [
        [
            'id' => '11a462b3a55a425cbf5a2a67e2f1d74a',
            'salesChannelId' => '3f05f1f6514d43f7ae11a669c7557d1c',
            'numberRangeTypeId' => 'bc7eb01d348e4546a7abe666f41e3648',
        ],
        [
            'id' => '46a541455bdd48b7a0e1da70abf0888e',
            'salesChannelId' => '98432def39fc4624b33213a56b8c944d',
            'numberRangeTypeId' => 'bc7eb01d348e4546a7abe666f41e3648',
        ],
    ],
    'translations' => [
        [
            'numberRangeId' => '54e9798c12614a198c6b96d87412646d',
            'name' => 'Invoices',
            'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
        ],
        [
            'numberRangeId' => '54e9798c12614a198c6b96d87412646d',
            'name' => 'Rechnungen',
            'languageId' => '445b8a01d83841369cf3c58d22481a3d',
        ],
    ],
];
