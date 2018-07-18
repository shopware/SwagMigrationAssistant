<?php

namespace SwagMigrationNext\Migration\Writer;

interface WriterRegistryInterface
{
    /**
     * Returns the writer which supports the given entity
     */
    public function getWriter(string $entityName): WriterInterface;
}