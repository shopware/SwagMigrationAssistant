<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProcessorNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-PROCESSOR-NOT-FOUND';

    public function __construct(string $profile, $gateway, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Processor for profile "%s" and gateway "%s" not found', $profile, $gateway);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
