<?php

namespace SwagMigrationNext\Migration\Writer;


use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WriterNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-MIGRATION-WRITER-NOT-FOUND';

    public function __construct(string $entityName, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Writer for "%s" entity not found', $entityName);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}