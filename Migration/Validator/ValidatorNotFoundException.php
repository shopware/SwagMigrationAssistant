<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Validator;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidatorNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-VALIDATOR-NOT-FOUND';

    public function __construct(string $entityName, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Validator for "%s" entity not found', $entityName);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
