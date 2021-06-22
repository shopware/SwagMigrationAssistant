<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware57\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;

class Shopware57NumberRangeConverter extends NumberRangeConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware57Profile::PROFILE_NAME
            && $migrationContext->getDataSet() !== null
            && $migrationContext->getDataSet()::getEntity() === NumberRangeDataSet::getEntity();
    }
}
