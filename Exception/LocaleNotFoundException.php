<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class LocaleNotFoundException extends ShopwareHttpException
{
    public function __construct(string $localeCode)
    {
        parent::__construct(
            'Locale entity for code "{{ localeCode }}" not found.',
            ['localeCode' => $localeCode]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__LOCALE_NOT_FOUND';
    }
}
