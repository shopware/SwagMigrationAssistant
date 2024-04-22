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
    public const ASSOCIATION_ENTITY_REQUIRED_MISSING = 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING';

    public const DATABASE_CONNECTION_ERROR = 'SWAG_MIGRATION__DATABASE_CONNECTION_ERROR';

    public const PLUGIN_NOT_INSTALLED = 'SWAG_MIGRATION__PLUGIN_NOT_INSTALLED';

    public static function associationEntityRequiredMissing(string $entity, string $missingEntity): self
    {
        return new AssociationEntityRequiredMissingException(
            Response::HTTP_NOT_FOUND,
            self::ASSOCIATION_ENTITY_REQUIRED_MISSING,
            'Mapping of "{{ missingEntity }}" is missing, but it is a required association for "{{ entity }}". Import "{{ missingEntity }}" first.',
            [
                'missingEntity' => $missingEntity,
                'entity' => $entity,
            ]
        );
    }

    public static function databaseConnectionError(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::DATABASE_CONNECTION_ERROR,
            'Database connection could not be established.'
        );
    }

    public static function pluginNotInstalled(): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PLUGIN_NOT_INSTALLED,
            'The required plugin is not installed in the source shop system. Please look up the documentation for this gateway.'
        );
    }
}
