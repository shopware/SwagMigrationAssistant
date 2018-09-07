<?php declare(strict_types=1);


namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CustomerExistsException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-SHOPWARE55-CUSTOMER-EXISTS';

    public function __construct(string $email, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Customer with email "%s" already exists', $email);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}