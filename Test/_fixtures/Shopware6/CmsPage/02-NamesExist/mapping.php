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
        'entityName' => DefaultEntities::LANGUAGE,
        'oldIdentifier' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
        'newIdentifier' => '5dd637353d044752ae6a8c6e7f53430b',
    ],
    [
        'connectionId' => 'global',
        'entityName' => DefaultEntities::CMS_PAGE,
        'oldIdentifier' => 'isDuplicate',
        'newIdentifier' => '6a4334b0b66b4029ae9598b8e6221f22',
    ],
];
