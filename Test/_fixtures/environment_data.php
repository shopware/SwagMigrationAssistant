<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'environmentInformation' => [
        'defaultShopLanguage' => 'de-DE',
        'shopwareVersion' => '___VERSION___',
        'versionText' => '___VERSION_TEXT___',
        'revision' => '___REVISION___',
        'additionalData' => [
            0 => [
                'id' => '1',
                'main_id' => null,
                'name' => 'Deutsch',
                'title' => null,
                'position' => '0',
                'host' => 'sw55.internal',
                'base_path' => '',
                'base_url' => null,
                'hosts' => '',
                'secure' => '0',
                'template_id' => '22',
                'document_template_id' => '22',
                'category_id' => '3',
                'locale_id' => '1',
                'currency_id' => '1',
                'customer_group_id' => '1',
                'fallback_id' => null,
                'customer_scope' => '0',
                'default' => '1',
                'active' => '1',
                'locale' => [
                    'id' => '1',
                    'locale' => 'de-DE',
                    'language' => 'Deutsch',
                    'territory' => 'Deutschland',
                ],
                'children' => [
                    0 => [
                        'id' => '3',
                        'main_id' => '1',
                        'name' => 'Englisch',
                        'title' => 'Englisch',
                        'position' => '0',
                        'host' => null,
                        'base_path' => null,
                        'base_url' => '/en',
                        'hosts' => '',
                        'secure' => '0',
                        'template_id' => null,
                        'document_template_id' => null,
                        'category_id' => '3',
                        'locale_id' => '2',
                        'currency_id' => '1',
                        'customer_group_id' => '1',
                        'fallback_id' => null,
                        'customer_scope' => '0',
                        'default' => '0',
                        'active' => '1',
                        'locale' => [
                            'id' => '2',
                            'locale' => 'en-GB',
                            'language' => 'Englisch',
                            'territory' => 'Vereinigtes Königreich',
                        ],
                    ],
                ],
            ],
        ],
        'products' => 37,
        'customers' => 2,
        'categories' => 8,
        'assets' => 23,
        'orders' => 0,
        'shops' => 2,
        'translations' => 0,
        'shoppingWorlds' => 1,
    ],
    'requestStatus' => new \SwagMigrationAssistant\Migration\RequestStatusStruct(),
];
