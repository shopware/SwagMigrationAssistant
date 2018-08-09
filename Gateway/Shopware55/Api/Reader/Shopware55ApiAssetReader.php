<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

class Shopware55ApiAssetReader extends Shopware55ApiAbstractReader
{
    public function __construct(int $offset, int $limit)
    {
        parent::__construct('Assets', $offset, $limit);
    }
}
