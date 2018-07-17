<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

interface Shopware55ApiReaderRegistryInterface
{
    public function getReader(string $entityName): Shopware55ApiReaderInterface;
}
