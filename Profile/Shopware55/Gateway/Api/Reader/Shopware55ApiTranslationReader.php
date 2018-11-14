<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

class Shopware55ApiTranslationReader extends Shopware55ApiAbstractReader
{
    public function __construct(int $offset, int $limit)
    {
        parent::__construct('Translations', $offset, $limit);
    }
}
