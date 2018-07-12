<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProfileNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-PROFILE-NOT-FOUND';

    public function __construct(string $profileName, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Profile "%s" not found', $profileName);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
