<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ConnectionCredentialsMissingException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-CONNECTION-CREDENTIALS-MISSING';

    public function __construct(int $code = 0, Throwable $previous = null)
    {
        parent::__construct('The given connection has no credentials.', $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
