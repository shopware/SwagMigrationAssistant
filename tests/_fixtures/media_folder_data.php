<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    0 => [
        'id' => '-1',
        'name' => 'Artikel',
        'parentID' => null,
        'position' => '2',
        'garbage_collectable' => '1',
        'setting' => [
            'id' => '10',
            'albumID' => '-1',
            'create_thumbnails' => '1',
            'thumbnail_size' => '200x200;600x600;1280x1280',
            'icon' => 'sprite-inbox',
            'thumbnail_high_dpi' => '1',
            'thumbnail_quality' => '90',
            'thumbnail_high_dpi_quality' => '60',
        ],
        '_locale' => 'de_DE',
    ],

    1 => [
        'id' => '2',
        'name' => 'Coole Artikel',
        'parentID' => '-1',
        'position' => '0',
        'garbage_collectable' => '1',
        '_locale' => 'de_DE',
    ],

    2 => [
        'id' => '3',
        'name' => 'Mega Coole Artikel',
        'parentID' => '2',
        'position' => '0',
        'garbage_collectable' => '1',
        'setting' => [
            'id' => '16',
            'albumID' => '3',
            'create_thumbnails' => '1',
            'thumbnail_size' => '200x200;600x600;1280x1280',
            'icon' => 'sprite-blue-folder',
            'thumbnail_high_dpi' => '1',
            'thumbnail_quality' => '90',
            'thumbnail_high_dpi_quality' => '60',
        ],
        '_locale' => 'de_DE',
    ],
];
