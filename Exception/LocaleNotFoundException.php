<?php declare(strict_types=1);

namespace SwagMigrationNext\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LocaleNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-LOCALE-NOT-FOUND';

    public function __construct(string $localeCode, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Locale entity for code "%s" not found', $localeCode);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
