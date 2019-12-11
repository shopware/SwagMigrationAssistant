<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class EntityNotExistsException extends ShopwareHttpException
{
    public function __construct(string $entityClassName, string $uuid)
    {
        parent::__construct(
            'No {{ entityClassName }} with UUID {{ uuid }} found. Make sure the entity with the UUID exists.',
            [
                'entityClassName' => $entityClassName,
                'uuid' => $uuid,
            ]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__ENTITY_NOT_EXISTS';
    }
}
