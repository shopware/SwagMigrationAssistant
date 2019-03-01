<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class EntityNotExistsException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-ENTITY-NOT-EXISTS';

    public function __construct(string $entityClassName, string $uuid, int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('No %s with UUID %s found. Make sure the entity with the UUID exists.', $entityClassName, $uuid);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
