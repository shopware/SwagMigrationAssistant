<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagMigrationAssistant\Test\Mock\Migration\Mapping\Dummy6MappingService;

return [
    'id' => Dummy6MappingService::DEFAULT_DELIVERY_TIME_UUID,
    'name' => '5-6 years',
    'min' => 5,
    'max' => 6,
    'unit' => 'year',
    'translations' => [
        [
            'name' => '5-6 years',
            'languageId' => '41248655c60c4dd58cde43f14cd4f149',
        ],
        [
            'name' => '5-6 Jahre',
            'languageId' => '5dd637353d044752ae6a8c6e7f53430b',
        ],
    ],
];
