<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\MigrationProcessorInterface;
use SwagMigrationAssistant\Migration\Run\MigrationStep;

#[Package('services-settings')]
/**
 * @internal
 */
class MigrationProcessorRegistry
{
    /**
     * @param iterable<MigrationProcessorInterface> $processors
     */
    public function __construct(private readonly iterable $processors)
    {
    }

    public function getProcessor(MigrationStep $step): MigrationProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($step)) {
                return $processor;
            }
        }

        throw MigrationException::unknownProgressStep($step->value);
    }
}
