<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class MigrationException extends HttpException
{
    final public const GATEWAY_READ = 'SWAG_MIGRATION__GATEWAY_READ';
    final public const PARENT_ENTITY_NOT_FOUND = 'SWAG_MIGRATION__SHOPWARE_PARENT_ENTITY_NOT_FOUND';
    final public const PROVIDER_HAS_NO_TABLE_ACCESS = 'SWAG_MIGRATION__PROVIDER_HAS_NO_TABLE_ACCESS';

    public static function gatewayRead(string $gateway): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::GATEWAY_READ,
            'Could not read from gateway: "{{ gateway }}".',
            ['gateway' => $gateway]
        );
    }

    public static function parentEntityForChildNotFound(string $entity, string $oldIdentifier): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::PARENT_ENTITY_NOT_FOUND,
            'Parent entity for "{{ entity }}: {{ oldIdentifier }}" child not found.',
            ['entity' => $entity, 'oldIdentifier' => $oldIdentifier]
        );
    }

    public static function providerHasNoTableAccess(string $identifier): self
    {
        return new self(
            Response::HTTP_NOT_IMPLEMENTED,
            self::PROVIDER_HAS_NO_TABLE_ACCESS,
            'Data provider "{{ identifier }}" has no direct table access found.',
            ['identifier' => $identifier]
        );
    }
}
