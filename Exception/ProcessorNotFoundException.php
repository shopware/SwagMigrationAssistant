<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProcessorNotFoundException extends ShopwareHttpException
{
    public function __construct(string $profile, string $gateway)
    {
        parent::__construct(
            'Processor for profile "{{ profile }}" and gateway "{{ gateway }}" not found.',
            [
                'profile' => $profile,
                'gateway' => $gateway,
            ]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND';
    }
}
