<?php

namespace SwagMigrationNext\Migration\Writer;

use IteratorAggregate;

class WriterRegistry implements WriterRegistryInterface
{
    /**
     * @var WriterInterface[]
     */
    private $writers;

    /**
     * @param IteratorAggregate $writers
     */
    public function __construct(IteratorAggregate $writers)
    {
        $this->writers = $writers;
    }

    /**
     * @param string $entityName
     * @return WriterInterface
     * @throws WriterNotFoundException
     */
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