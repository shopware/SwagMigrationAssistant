<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

interface Shopware55LocalReaderRegistryInterface
{
    public function getReader(string $entityName): Shopware55LocalReaderInterface;
}
