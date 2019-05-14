<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MigrationWorkloadPropertyMissingException extends ShopwareHttpException
{
    public function __construct(string $property)
    {
        parent::__construct(
            'Required property "{{ property }}" for migration workload is missing',
            ['property' => $property]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__WORKLOAD_PROPERTY_MISSING';
    }
}
