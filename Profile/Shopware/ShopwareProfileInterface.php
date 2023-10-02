<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

#[Package('services-settings')]
interface ShopwareProfileInterface extends ProfileInterface
{
}
