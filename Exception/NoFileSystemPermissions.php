<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NoFileSystemPermissions extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-NO-FILE-SYSTEM-PERMISSIONS';

    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct('No file system permissions to create or write to files or directories', $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
