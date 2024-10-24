<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagMigrationAssistant\Migration\Mapping\Lookup\StateMachineStateLookup;

return [
    StateMachineStateLookup::class => [
        [
            'input' => ['open', 'order . state'],
            'output' => '775b8a01d83841369cf3c58d22481a3d',
        ],
        [
            'input' => ['open', 'order_transaction . state'],
            'output' => '665b8a01d83841369cf3c58d22481a3d',
        ],
    ],
];
