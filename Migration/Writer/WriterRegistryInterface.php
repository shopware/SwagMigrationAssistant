<?php

namespace SwagMigrationNext\Migration\Writer;

interface WriterRegistryInterface
{
    /**
     * Returns the writer which supports the given entity
     *
     * @param string $entityName
     * @return WriterInterface
     * @throws WriterNotFoundException
     */
    public function getWriter(string $entityName): WriterInterface;
}