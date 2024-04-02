<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    [
        'id' => '5',
        'description' => 'Blackfriday',
        'vouchercode' => 'BLACK2020',
        'numberofunits' => '1',
        'value' => '10',
        'shippingfree' => '0',
        'ordercode' => 'BLACKFRDAY2020',
        'modus' => '0',
        'percental' => '1',
        'numorder' => '10',
        'strict' => '0',
        'taxconfig' => '',
    ],
    [
        'id' => '5',
        'description' => 'Blackfriday',
        'numberofunits' => '1',
        'value' => '10',
        'shippingfree' => '0',
        'ordercode' => 'BLACKFRDAY2020',
        'modus' => '0',
        'percental' => '1',
        'numorder' => '10',
        'strict' => '0',
        'taxconfig' => '',
        'individualCodes' => [
            [
                'id' => '1',
                'voucherID' => '4',
                'userID' => null,
                'code' => '23A7BCA4',
                'cashed' => '0',
                'firstname' => null,
                'lastname' => null,
            ],
            [
                'id' => '2',
                'voucherID' => '4',
                'userID' => '5',
                'code' => '23A7BDA0',
                'cashed' => '1',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
            ],
            [
                'id' => '3',
                'voucherID' => '4',
                'userID' => '8',
                'code' => '23A7BDD9',
                'cashed' => '0',
                'firstname' => 'Maxi',
                'lastname' => 'Mustermanns',
            ],
            [
                'id' => '4',
                'voucherID' => '4',
                'userID' => null,
                'code' => '23A7BDF9',
                'cashed' => '0',
                'firstname' => null,
                'lastname' => null,
            ],
        ],
    ],
];
