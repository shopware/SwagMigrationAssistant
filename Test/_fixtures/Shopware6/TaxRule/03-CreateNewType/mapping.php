<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

return [
    [
        'entityName' => DefaultEntities::TAX_RULE,
        'oldIdentifier' => '007a87b622384cbeb4d5addbaa887259',
        'newIdentifier' => '8ed3ed1fc3b943e8b64e7ce66e669c6e',
    ],
    [
        'entityName' => DefaultEntities::TAX,
        'oldIdentifier' => '9b52e8c9cf57475e911f32ebb0268de5',
        'newIdentifier' => '8ed3ed1fc3b943e8b64e7ce66e669c6e',
    ],
    [
        'entityName' => DefaultEntities::COUNTRY,
        'oldIdentifier' => '3cf45da2aa1b4017afdaad51861a9a75',
        'newIdentifier' => '5388154b58754d67a29a87a84d795fc4',
    ],
];
