<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class DatabaseConnectionException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'Database connection could not be established.'
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__DATABASE_CONNECTION_ERROR';
    }
}
