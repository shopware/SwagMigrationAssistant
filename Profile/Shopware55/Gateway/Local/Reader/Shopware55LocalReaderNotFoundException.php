<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class Shopware55LocalReaderNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-SHOPWARE55-LOCAL-READER-NOT-FOUND';

    public function __construct(string $entityName, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Shopware55 local reader for "%s" not found', $entityName);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
