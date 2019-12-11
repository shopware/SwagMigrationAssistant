<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Profile\Dummy;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerConverter;
use SwagMigrationAssistant\Test\Mock\DataSet\InvalidCustomerDataSet;

class DummyInvalidCustomerConverter extends Shopware55CustomerConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getDataSet()::getEntity() === InvalidCustomerDataSet::getEntity();
    }
}
