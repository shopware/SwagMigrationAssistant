<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MigrationIsRunningException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-IS-RUNNING';

    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('A Migration is currently Running. You can not perform this action now.', $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
