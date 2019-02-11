<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Mapping;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;

interface Shopware55MappingServiceInterface extends MappingServiceInterface
{
    public function getPaymentUuid(string $technicalName, Context $context): ?string;

    public function getOrderStateUuid(int $oldStateId, Context $context): ?string;

    public function getTransactionStateUuid(int $oldStateId, Context $context): ?string;
}
