<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MigrationIsRunningException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('A migration is currently running. You can not perform this action now.');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__IS_RUNNING';
    }
}
