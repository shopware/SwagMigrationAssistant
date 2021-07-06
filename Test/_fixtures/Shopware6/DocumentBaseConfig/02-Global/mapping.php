<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

return [
    [
        'entityName' => DefaultEntities::ORDER_DOCUMENT_TYPE,
        'oldIdentifier' => 'delivery_note',
        'newIdentifier' => '41248655c60c4dd58cde43f14cd4f149',
    ],
    [
        'entityName' => DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG,
        'oldIdentifier' => '285c94d513214fbdafd2b2ba84d630e4',
        'newIdentifier' => '03d042cfce284f5a98ad6c417dc26236',
    ],
];
