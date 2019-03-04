<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MigrationWorkloadPropertyMissingException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-WORKLOAD-PROPERTY-MISSING';

    public function __construct(string $property, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Required property "%s" for migration workload is missing', $property);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
