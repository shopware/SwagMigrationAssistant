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
    public const GATEWAY_READ = 'SWAG_MIGRATION__GATEWAY_READ';

    public static function gatewayRead(string $gateway): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::GATEWAY_READ,
            'Could not read from gateway: "{{ gateway }}".',
            ['gateway' => $gateway]
        );
    }
}
