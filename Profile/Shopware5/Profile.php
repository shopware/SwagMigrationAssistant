<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware5;

use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\ProfileInterface;

class Profile implements ProfileInterface
{
    public const PROFILE_NAME = 'shopware5.5';

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function getData(GatewayInterface $gateway, MigrationContext $context): array
    {
        return $gateway->read($context->getEntityType());
    }
}
