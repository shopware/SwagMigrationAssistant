<?php declare(strict_types=1);


namespace SwagMigrationNext\Migration\Mapping;


use Shopware\Core\Framework\ShopwareHttpException;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

class LocaleNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-VALIDATOR-NOT-FOUND';

    public function __construct(string $localeCode, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Locale for locale code "%s" not found', $localeCode);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}