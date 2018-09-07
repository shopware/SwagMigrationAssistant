<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ParentEntityForChildNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-SHOPWARE55-PARENT-ENTITY-NOT-FOUND';

    public function __construct(string $entity, string $oldIdentifier, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Parent entity for "%s: %s" child not found', $entity, $oldIdentifier);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
