<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProfileNotFoundException extends ShopwareHttpException
{
    public function __construct(string $profileName)
    {
        parent::__construct(
            'Profile "{{ profileName }}" not found.',
            ['profileName' => $profileName]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__PROFILE_NOT_FOUND';
    }
}
