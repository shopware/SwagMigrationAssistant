<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class DataSetNotFoundException extends ShopwareHttpException
{
    public function __construct(string $entity)
    {
        parent::__construct(
            'Data set for "{{ entity }}" entity not found.',
            ['entity' => $entity]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__DATASET_NOT_FOUND';
    }
}
