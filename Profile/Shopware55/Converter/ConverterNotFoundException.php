<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ConverterNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-SHOPWARE55-CONVERTER-NOT-FOUND';

    public function __construct(string $entity, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Converter for "%s" entity not found', $entity);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
