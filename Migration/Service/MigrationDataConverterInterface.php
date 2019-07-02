<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MigrationDataConverterInterface
{
    public function convert(array $data, MigrationContextInterface $migrationContext, Context $context): void;
}
