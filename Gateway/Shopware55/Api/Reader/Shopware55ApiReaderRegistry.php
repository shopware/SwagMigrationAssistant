<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use IteratorAggregate;

class Shopware55ApiReaderRegistry implements Shopware55ApiReaderRegistryInterface
{
    /**
     * @var Shopware55ApiReaderInterface[]
     */
    private $readers;

    public function __construct(IteratorAggregate $readers)
    {
        $this->readers = $readers;
    }

    /**
     * @throws Shopware55ReaderNotFoundException
     */
    public function getReader(string $entityName): Shopware55ApiReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports() === $entityName) {
                return $reader;
            }
        }

        throw new Shopware55ReaderNotFoundException($entityName);
    }
}
