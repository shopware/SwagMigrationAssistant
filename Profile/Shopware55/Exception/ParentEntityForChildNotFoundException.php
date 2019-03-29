<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ParentEntityForChildNotFoundException extends ShopwareHttpException
{
    public function __construct(string $entity, string $oldIdentifier)
    {
        parent::__construct(
            'Parent entity for "{{ entity }}: {{ oldIdentifier }}" child not found.',
            [
                'entity' => $entity,
                'oldIdentifier' => $oldIdentifier,
            ]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__SHOPWARE55_PARENT_ENTITY_NOT_FOUND';
    }
}
