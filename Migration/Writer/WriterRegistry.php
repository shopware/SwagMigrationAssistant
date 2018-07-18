<?php

namespace SwagMigrationNext\Migration\Writer;

use IteratorAggregate;

class WriterRegistry implements WriterRegistryInterface
{
    /**
     * @var WriterInterface[]
     */
    private $writers;

    public function __construct(IteratorAggregate $writers)
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