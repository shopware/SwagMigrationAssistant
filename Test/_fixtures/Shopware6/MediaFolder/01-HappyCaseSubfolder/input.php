<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'id' => 'f96a8ab318484eceb2ca1b4dcdb5afd1',
    'defaultFolder' => [
        'id' => 'c2187cd37ca445c2a000104cfa8a13d5',
        'associationFields' => '["documents"]',
        'entity' => 'document',
    ],
    'name' => 'Subfolder of Document Media',
    'configuration' => [
        'id' => '5875e071ffae4724b41aaa64098078df',
        'createThumbnails' => 1,
        'thumbnailQuality' => 80,
        'keepAspectRatio' => 1,
        'private' => 1,
        'mediaThumbnailSizes' => [
            [
                'id' => 'a8084c815a654adaa91496fc1c921923',
                'width' => 800,
                'height' => 800,
            ],
            [
                'id' => '3f6464a69b9946fb9ecdab0cd3056d78',
                'width' => 400,
                'height' => 400,
            ],
            [
                'id' => 'c6e6726b31df4dd0ac77b38c19ce89a8',
                'width' => 1920,
                'height' => 1920,
            ],
        ],
    ],
    'useParentConfiguration' => 1,
];
