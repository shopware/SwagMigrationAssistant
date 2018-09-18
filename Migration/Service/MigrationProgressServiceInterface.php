<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;

interface MigrationProgressServiceInterface
{
    public function getProgress(Context $context): array;
}
