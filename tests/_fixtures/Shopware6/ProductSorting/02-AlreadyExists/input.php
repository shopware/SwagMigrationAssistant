<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => 'ff255b36dadf4eedaea4599f48a87ff9',
    'key' => 'lagerbestand',
    'priority' => 1,
    'active' => true,
    'fields' => [
        [
            'field' => 'product.stock',
            'order' => 'desc',
            'priority' => 1,
            'naturalSorting' => 0,
        ],
    ],
    'label' => 'Lagerbestand',
    'translations' => [
        [
            'label' => 'Lagerbestand',
            'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
        ],
    ],
    'locked' => false,
];
