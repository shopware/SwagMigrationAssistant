<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Defaults;
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
        'entityName' => DefaultEntities::CURRENCY,
        'oldIdentifier' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
        'newIdentifier' => 'b7d2554b0ce847cd82f3ac9bd1c0dfcb',
    ],
    [
        'entityName' => DefaultEntities::CURRENCY,
        'oldIdentifier' => '18b930393f8b41bd92bb7ca3e8ef5429',
        'newIdentifier' => '18b930393f8b41bd92bb7ca3e8ef542a',
    ],
    [
        'entityName' => DefaultEntities::COUNTRY,
        'oldIdentifier' => '580a3bdd1739487f99ddd56ec365828f',
        'newIdentifier' => '580a3bdd1739487f99ddd56ec3658290',
    ],
    [
        'entityName' => DefaultEntities::COUNTRY,
        'oldIdentifier' => '972a1788172c432db0eb6e2517c92e5e',
        'newIdentifier' => '972a1788172c432db0eb6e2517c92e5f',
    ],
    [
        'entityName' => DefaultEntities::PAYMENT_METHOD,
        'oldIdentifier' => 'c5917da8076b495ba80b14a61afd90fb',
        'newIdentifier' => 'c5917da8076b495ba80b14a61afd90fc',
    ],
    [
        'entityName' => DefaultEntities::SHIPPING_METHOD,
        'oldIdentifier' => '6e028d0f24114544ad25235d56bb8846',
        'newIdentifier' => '6e028d0f24114544ad25235d56bb8847',
    ],
    [
        'entityName' => DefaultEntities::CATEGORY,
        'oldIdentifier' => '7515fecfbfe14fbb87892873dbd1134d',
        'newIdentifier' => '7515fecfbfe14fbb87892873dbd1134e',
    ],
    [
        'entityName' => DefaultEntities::CATEGORY,
        'oldIdentifier' => '77b959cf66de4c1590c7f9b7da3982f3',
        'newIdentifier' => '77b959cf66de4c1590c7f9b7da3982f4',
    ],
    [
        'entityName' => DefaultEntities::CATEGORY,
        'oldIdentifier' => '19ca405790ff4f07aac8c599d4317868',
        'newIdentifier' => '19ca405790ff4f07aac8c599d4317869',
    ],
    [
        'entityName' => DefaultEntities::CUSTOMER_GROUP,
        'oldIdentifier' => 'cfbd5018d38d41d8adca10d94fc8bdd6',
        'newIdentifier' => 'cfbd5018d38d41d8adca10d94fc8bdd7',
    ],
    [
        'entityName' => DefaultEntities::SALES_CHANNEL,
        'oldIdentifier' => Defaults::SALES_CHANNEL,
        'newIdentifier' => 'cfbd5018d38d41d8adca10d94fc8bdd7',
    ],
];
