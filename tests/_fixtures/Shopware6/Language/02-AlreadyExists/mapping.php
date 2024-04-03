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
        'oldIdentifier' => 'en-GB',
        'newIdentifier' => Defaults::LANGUAGE_SYSTEM,
    ],
];
