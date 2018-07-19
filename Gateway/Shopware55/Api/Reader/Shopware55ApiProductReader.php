<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use Shopware\Core\Content\Product\ProductDefinition;

class Shopware55ApiProductReader implements Shopware55ApiReaderInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function read(Shopware55ApiClient $apiClient): array
    {
        return $apiClient->get('articles')['data'];
    }
}
