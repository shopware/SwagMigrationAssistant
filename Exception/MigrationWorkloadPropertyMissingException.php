<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class MigrationWorkloadPropertyMissingException extends ShopwareHttpException
{
    public function __construct(string $property)
    {
        parent::__construct(
            'Required property "{{ property }}" for migration workload is missing',
            ['property' => $property]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__WORKLOAD_PROPERTY_MISSING';
    }
}
