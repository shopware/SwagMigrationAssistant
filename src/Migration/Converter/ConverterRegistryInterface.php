<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface ConverterRegistryInterface
{
    /**
     * Returns the converter which supports the given internal entity
     */
    public function getConverter(MigrationContextInterface $migrationContext): ConverterInterface;
}
