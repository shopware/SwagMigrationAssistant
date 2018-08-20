<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AssociationEntityRequiredMissingException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-SHOPWARE55-ASSOCIATION-REQUIRED-MISSING';

    public function __construct(string $entity, string $missingEntity, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Mapping of "%s" is missing, but it is a required association for "%s". Import "%s" first', $missingEntity, $entity, $missingEntity);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
