<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class RequestCertificateInvalidException extends ShopwareHttpException
{
    public function __construct(string $url)
    {
        parent::__construct(
            'The following cURL request failed with an SSL certificate problem: "{{ url }}"',
            ['url' => $url]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__REQUEST_CERTIFICATE_INVALID';
    }
}
