<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class SslRequiredException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'The request failed, because SSL is required.'
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_MISDIRECTED_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__SSL_REQUIRED';
    }
}
