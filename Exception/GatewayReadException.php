<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class GatewayReadException extends ShopwareHttpException
{
    public function __construct(string $gateway, int $code = 0)
    {
        parent::__construct(
            'Could not read from gateway: "{{ gateway }}".',
            ['gateway' => $gateway]
        );
        $this->code = $code;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__GATEWAY_READ';
    }
}
