<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ConnectionCredentialsMissingException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('The given connection has no credentials.');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__CONNECTION_CREDENTIALS_MISSING';
    }
}
