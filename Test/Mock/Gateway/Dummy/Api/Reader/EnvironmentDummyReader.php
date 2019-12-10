<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader;

class EnvironmentDummyReader extends EnvironmentReader
{
    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        return [
            'environmentInformation' => [
                'defaultShopLanguage' => 'de-DE',
                'defaultCurrency' => 'EUR',
                'shopwareVersion' => '___VERSION___',
                'versionText' => '___VERSION_TEXT___',
                'revision' => '___REVISION___',
                'additionalData' => [
                    [
                        'id' => '1',
                        'main_id' => null,
                        'position' => '0',
                        'title' => null,
                        'host' => 'shopware56.local',
                        'base_path' => null,
                        'base_url' => null,
                        'name' => 'Deutsch',
                        'hosts' => '',
                        'secure' => '0',
                        'template_id' => '23',
                        'document_template_id' => '23',
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
                            [
                                'id' => '2',
                                'main_id' => '1',
                                'name' => 'English',
                                'title' => 'English',
                                'position' => '0',
                                'host' => null,
                                'base_path' => null,
                                'base_url' => null,
                                'hosts' => '',
                                'secure' => '0',
                                'template_id' => null,
                                'document_template_id' => null,
                                'category_id' => '39',
                                'locale_id' => '2',
                                'currency_id' => '1',
                                'customer_group_id' => '1',
                                'fallback_id' => '2',
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
                            [
                                'id' => '3',
                                'main_id' => '1',
                                'name' => 'Dansk',
                                'title' => null,
                                'position' => '0',
                                'host' => null,
                                'base_path' => null,
                                'base_url' => null,
                                'hosts' => '',
                                'secure' => '0',
                                'template_id' => null,
                                'document_template_id' => null,
                                'category_id' => '39',
                                'locale_id' => '41',
                                'currency_id' => '1',
                                'customer_group_id' => '1',
                                'fallback_id' => null,
                                'customer_scope' => '0',
                                'default' => '0',
                                'active' => '1',
                                'locale' => [
                                    'id' => '41',
                                    'locale' => 'da-DK',
                                    'language' => 'Dänisch',
                                    'territory' => 'Dänemark',
                                ],
                            ],
                        ],
                    ],
                ],
                'updateAvailable' => false,
            ],
            'requestStatus' => new RequestStatusStruct(),
        ];
    }
}
