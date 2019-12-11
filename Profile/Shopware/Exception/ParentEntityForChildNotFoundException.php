<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ParentEntityForChildNotFoundException extends ShopwareHttpException
{
    public function __construct(string $entity, string $oldIdentifier)
    {
        parent::__construct(
            'Parent entity for "{{ entity }}: {{ oldIdentifier }}" child not found.',
            [
                'entity' => $entity,
                'oldIdentifier' => $oldIdentifier,
            ]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__SHOPWARE_PARENT_ENTITY_NOT_FOUND';
    }
}
