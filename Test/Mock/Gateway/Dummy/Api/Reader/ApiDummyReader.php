<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class ApiDummyReader
{
    public function supports(): string
    {
        return DefaultEntities::PRODUCT;
    }

    public function read(): array
    {
        return require __DIR__ . '/../../../../../_fixtures/product_data.php';
    }
}
