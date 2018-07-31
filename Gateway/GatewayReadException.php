<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GatewayReadException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-GATEWAY-READ';

    public function __construct(string $gateway, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Could not read from gateway: %s', $gateway);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
