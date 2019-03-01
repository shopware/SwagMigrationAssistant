<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class GatewayNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-GATEWAY-NOT-FOUND';

    public function __construct(string $gatewayName, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Gateway "%s" not found', $gatewayName);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
