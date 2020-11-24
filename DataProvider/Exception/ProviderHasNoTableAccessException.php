<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProviderHasNoTableAccessException extends ShopwareHttpException
{
    public function __construct(string $identifier)
    {
        parent::__construct(
            'Data provider "{{ identifier }}" has no direct table access found.',
            ['identifier' => $identifier]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_IMPLEMENTED;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__PROVIDER_HAS_NO_TABLE_ACCESS';
    }
}
