<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class FooDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return 'foo';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return true;
    }
}
