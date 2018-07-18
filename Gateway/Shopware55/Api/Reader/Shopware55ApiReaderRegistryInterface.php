<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

interface Shopware55ApiReaderRegistryInterface
{
    /**
     * Returns the reader for the given entity name
     */
    public function getReader(string $entityName): Shopware55ApiReaderInterface;
}
