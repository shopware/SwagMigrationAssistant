<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Media;

use SwagMigrationNext\Exception\ProcessorNotFoundException;
use SwagMigrationNext\Migration\MigrationContextInterface;

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
    public function getProcessor(MigrationContextInterface $context): MediaFileProcessorInterface
    {
        $profileName = $context->getProfileName();
        $gatewayName = $context->getGatewayName();
        foreach ($this->processors as $processor) {
            if ($processor->supports($profileName, $gatewayName)) {
                return $processor;
            }
        }

        throw new ProcessorNotFoundException($profileName, $gatewayName);
    }
}
