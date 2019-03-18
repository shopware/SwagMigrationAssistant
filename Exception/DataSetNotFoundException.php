<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class DataSetNotFoundException extends ShopwareHttpException
{
    public function __construct(string $entity, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Data set for "%s" entity not found', $entity);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
