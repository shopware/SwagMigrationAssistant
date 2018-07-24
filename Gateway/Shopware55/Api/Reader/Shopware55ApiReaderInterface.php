<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use GuzzleHttp\Client;

interface Shopware55ApiReaderInterface
{
    /**
     * Identifier which external entity this reader supports
     */
    public function supports(): string;

    /**
     * Reads data from its entity with the given API client
     */
    public function read(Client $apiClient): array;
}
