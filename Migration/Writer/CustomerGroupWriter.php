<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class CustomerGroupWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::CUSTOMER_GROUP;
    }
}
