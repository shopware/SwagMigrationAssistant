<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class SslRequiredException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'The request failed, because SSL is required.'
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_MISDIRECTED_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__SSL_REQUIRED';
    }
}
