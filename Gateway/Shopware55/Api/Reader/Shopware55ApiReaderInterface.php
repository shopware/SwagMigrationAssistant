<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

interface Shopware55ApiReaderInterface
{
    public function supports(): string;

    public function read(Shopware55ApiClient $apiClient): array;
}
