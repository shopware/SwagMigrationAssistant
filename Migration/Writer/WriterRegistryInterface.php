<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Exception\WriterNotFoundException;

interface WriterRegistryInterface
{
    /**
     * Returns the writer which supports the given entity
     *
     * @throws WriterNotFoundException
     */
    public function getWriter(string $entityName): WriterInterface;
}
