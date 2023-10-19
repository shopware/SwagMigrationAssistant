<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Writer;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

#[Package('services-settings')]
class ProductManufacturerWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::PRODUCT_MANUFACTURER;
    }
}
