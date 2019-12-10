<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableCountReader;

class TableCountDummyReader extends TableCountReader
{
    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        return [];
    }
}
