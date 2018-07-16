<?php

namespace SwagMigrationNext\Migration\Writer;

interface WriterRegistryInterface
{
    /**
     * @param string $entityName
     * @return WriterInterface
     * @throws WriterNotFoundException
     */
    public function getWriter(string $entityName): WriterInterface;
}