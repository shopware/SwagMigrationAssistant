<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Exception;

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
