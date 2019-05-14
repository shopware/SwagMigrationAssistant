<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MigrationRunUndefinedStatusException extends ShopwareHttpException
{
    public function __construct(string $status)
    {
        parent::__construct(
            'Migration run status "{{ status }}" is not a valid status.',
            ['status' => $status]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__RUN_UNDEFINED_STATUS';
    }
}
