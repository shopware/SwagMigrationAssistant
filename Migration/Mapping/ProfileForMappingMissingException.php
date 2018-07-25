<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProfileForMappingMissingException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-PROFILE-FOR-MAPPING-MISSING';

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
