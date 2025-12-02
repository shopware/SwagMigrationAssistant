<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\DataSet;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('after-sales')]
class DataSetMock extends DataSet
{
    private static string $entityName = 'defaultDatasetEntityName';

    private bool $supports;

    public function __construct(bool $supports = true)
    {
        $this->supports = $supports;
    }

    public static function getEntity(): string
    {
        return self::$entityName;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $this->supports;
    }
}
