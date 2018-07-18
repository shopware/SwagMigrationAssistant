<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

interface Shopware55ApiReaderInterface
{
    /**
     * Identifier which external entity this reader supports
     */
    public function supports(): string;

    /**
     * Reads data from its entity with the given API client
     */
    public function read(Shopware55ApiClient $apiClient): array;
}
