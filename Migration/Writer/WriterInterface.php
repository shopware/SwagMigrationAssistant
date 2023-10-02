<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface WriterInterface
{
    /**
     * Identifier which internal entity this writer supports
     */
    public function supports(): string;

    /**
     * Writes the converted data of the supported entity type into the database
     */
    public function writeData(array $data, Context $context): ?array;
}
