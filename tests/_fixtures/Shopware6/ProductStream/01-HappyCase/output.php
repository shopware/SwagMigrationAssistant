<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'name' => 'Test',
    'description' => 'Beschreibung',
    'filters' => [
        [
            'type' => 'equals',
            'field' => 'stock',
            'position' => 1,
            'parameters' => null,
            'id' => '3ea8b37a2cd94beaab997db80d3f5ec2',
        ],
        [
            'type' => 'multi',
            'operator' => 'OR',
            'position' => 0,
            'parameters' => null,
            'id' => '498cd52a1dd64f87b9415009b17518b1',
        ],
        [
            'type' => 'multi',
            'operator' => 'AND',
            'position' => 0,
            'parameters' => null,
            'id' => '949f58ccb0304923a1acf56ba3d7aaca',
        ],
        [
            'type' => 'equals',
            'field' => 'price',
            'value' => '5',
            'position' => 0,
            'parameters' => null,
            'id' => 'a1cfa66e0f5b4838a06399a46e5912bf',
        ],
        [
            'type' => 'equals',
            'field' => 'active',
            'value' => '0',
            'position' => 0,
            'parameters' => null,
            'id' => 'a34ba9b4a7214ad6bfb1f51a54e2c993',
        ],
        [
            'type' => 'range',
            'field' => 'stock',
            'position' => 0,
            'parameters' => [
                'gt' => 1,
            ],
            'id' => 'd5a162395b944813a6387b5dc3b44b26',
        ],
        [
            'type' => 'multi',
            'operator' => 'OR',
            'position' => 2,
            'parameters' => null,
            'id' => 'f2a774f3475d441dbfb487d2c0cdd885',
        ],
        [
            'type' => 'not',
            'position' => 1,
            'parameters' => null,
            'id' => 'f4f52813b5374cd7acf798f61d2cb0d0',
        ],
    ],
    'translations' => [
        [
            'name' => 'Test',
            'description' => 'Beschreibung',
            'languageId' => '41248655c60c4dd58cde43f14cd4f149',
        ],
    ],
    'id' => '8d826a6010c443d595b1b25a1911326e',
];
