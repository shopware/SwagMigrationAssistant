<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Exception\WriterNotFoundException;

class WriterRegistry implements WriterRegistryInterface
{
    /**
     * @var WriterInterface[]
     */
    private $writers;

    public function __construct(iterable $writers)
    {
        $this->writers = $writers;
    }

    public function getWriter(string $entityName): WriterInterface
    {
        foreach ($this->writers as $writer) {
            if ($writer->supports() === $entityName) {
                return $writer;
            }
        }

        throw new WriterNotFoundException($entityName);
    }
}
