<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use SwagMigrationAssistant\Exception\ProcessorNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MediaFileProcessorRegistryInterface
{
    /**
     * @throws ProcessorNotFoundException
     */
    public function getProcessor(MigrationContextInterface $context): MediaFileProcessorInterface;
}
