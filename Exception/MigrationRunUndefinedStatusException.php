<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MigrationRunUndefinedStatusException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-RUN-UNDEFINED-STATUS';

    public function __construct(string $status, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Migration run status "%s" is not a valid status', $status);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
