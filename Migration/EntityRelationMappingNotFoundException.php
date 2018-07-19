<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EntityRelationMappingNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-ENTITY-RELATION-MAPPING-NOT-FOUND';

    public function __construct(string $entityName, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Relation mapping for "%s" entity not found', $entityName);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
