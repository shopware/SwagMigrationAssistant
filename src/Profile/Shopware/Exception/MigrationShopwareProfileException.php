<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Exception;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class MigrationShopwareProfileException extends HttpException
{
    public const PLUGIN_NOT_INSTALLED = 'SWAG_MIGRATION__PLUGIN_NOT_INSTALLED';

    public static function pluginNotInstalled(): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PLUGIN_NOT_INSTALLED,
            'The required plugin is not installed in the source shop system. Please look up the documentation for this gateway.'
        );
    }
}
