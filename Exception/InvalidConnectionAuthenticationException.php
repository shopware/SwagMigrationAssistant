<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InvalidConnectionAuthenticationException extends ShopwareHttpException
{
    public function __construct(string $url)
    {
        parent::__construct(
            'Invalid connection authentication for the request: "{{ url }}"',
            ['url' => $url]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__CONNECTION_AUTHENTICATION_INVALID';
    }
}
