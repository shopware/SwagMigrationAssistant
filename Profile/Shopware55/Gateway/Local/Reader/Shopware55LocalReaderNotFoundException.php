<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class Shopware55LocalReaderNotFoundException extends ShopwareHttpException
{
    public function __construct(string $entityName)
    {
        parent::__construct(
            'Shopware55 local reader for "{{ entityName }}" not found.',
            ['entityName' => $entityName]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__SHOPWARE55_LOCAL_READER_NOT_FOUND';
    }
}
