<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class PluginNotInstalledException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'The required plugin is not installed in the source shop system. Please look up the documentation for this gateway.'
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__PLUGIN_NOT_INSTALLED';
    }
}
