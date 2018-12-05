<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use SwagMigrationNext\Exception\ProcessorNotFoundException;
use SwagMigrationNext\Migration\MigrationContext;

class MediaFileProcessorRegistry implements MediaFileProcessorRegistryInterface
{
    /**
     * @var MediaFileProcessorInterface[]
     */
    private $processors;

    public function __construct(iterable $processors)
    {
        $this->processors = $processors;
    }

    /**
     * @throws ProcessorNotFoundException
     */
    public function getProcessor(MigrationContext $context): MediaFileProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($context->getProfileName(), $context->getGateway())) {
                return $processor;
            }
        }

        throw new ProcessorNotFoundException($context->getProfileName(), $context->getGateway());
    }
}
