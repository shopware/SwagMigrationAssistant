<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

return [
    [
        'entityName' => DefaultEntities::MEDIA_THUMBNAIL_SIZE,
        'oldIdentifier' => '800-800',
        'newIdentifier' => 'ba1f29992b9d48d88aaa165bc25403aa',
    ],
    [
        'entityName' => DefaultEntities::MEDIA_THUMBNAIL_SIZE,
        'oldIdentifier' => '400-400',
        'newIdentifier' => 'bbc51213d8254b3896e722165dbb1229',
    ],
    [
        'entityName' => DefaultEntities::MEDIA_THUMBNAIL_SIZE,
        'oldIdentifier' => '1920-1920',
        'newIdentifier' => '631e8285bcdd4e7199bb18aac4800eeb',
    ],
    [
        'entityName' => DefaultEntities::MEDIA_DEFAULT_FOLDER,
        'oldIdentifier' => 'document',
        'newIdentifier' => 'c2187cd37ca445c2a000104cfa8a13d5',
    ],
];
