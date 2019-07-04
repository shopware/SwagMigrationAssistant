<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class AssociationEntityRequiredMissingException extends ShopwareHttpException
{
    public function __construct(string $entity, string $missingEntity)
    {
        parent::__construct(
            'Mapping of "{{ missingEntity }}" is missing, but it is a required association for "{{ entity }}". Import "{{ missingEntity }}" first.',
            [
                'missingEntity' => $missingEntity,
                'entity' => $entity,
            ]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING';
    }
}
