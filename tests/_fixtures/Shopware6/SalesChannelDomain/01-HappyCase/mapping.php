<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

return [
    [
        'entityName' => DefaultEntities::LANGUAGE,
        'oldIdentifier' => '24f78d24f14849f6922ab2f76dcd76e8',
        'newIdentifier' => '41248655c60c4dd58cde43f14cd4f149',
    ],
    [
        'entityName' => DefaultEntities::CURRENCY,
        'oldIdentifier' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
        'newIdentifier' => 'b7d2554b0ce847cd82f3ac9bd1c0dfcb',
    ],
    [
        'entityName' => DefaultEntities::SALES_CHANNEL,
        'oldIdentifier' => '3f05f1f6514d43f7ae11a669c7557d1c',
        'newIdentifier' => '3f05f1f6514d43f7ae11a669c7557d1d',
    ],
    [
        'entityName' => DefaultEntities::SNIPPET_SET,
        'oldIdentifier' => 'bdfee7596cad46efb4e0939e9dd4323e',
        'newIdentifier' => 'bdfee7596cad46efb4e0939e9dd4323f',
    ],
];
