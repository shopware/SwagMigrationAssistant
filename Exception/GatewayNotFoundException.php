<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class GatewayNotFoundException extends ShopwareHttpException
{
    public function __construct(string $gatewayName)
    {
        parent::__construct(
            'Gateway "{{ gatewayName }}" not found.',
            ['gatewayName' => $gatewayName]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__GATEWAY_NOT_FOUND';
    }
}
