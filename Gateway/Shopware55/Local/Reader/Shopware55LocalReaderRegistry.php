<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

use IteratorAggregate;

class Shopware55LocalReaderRegistry implements Shopware55LocalReaderRegistryInterface
{
    /**
     * @var Shopware55LocalReaderInterface[]
     */
    private $readers;

    public function __construct(IteratorAggregate $readers)
    {
        $this->readers = $readers;
    }

    /**
     * @throws Shopware55LocalReaderNotFoundException
     */
    public function getReader(string $entityName): Shopware55LocalReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports() === $entityName) {
                return $reader;
            }
        }

        throw new Shopware55LocalReaderNotFoundException($entityName);
    }
}
