<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MigrationContextPropertyMissingException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-CONTEXT-PROPERTY-MISSING';

    public function __construct(string $property, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Required property "%s" for migration context is missing', $property);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
