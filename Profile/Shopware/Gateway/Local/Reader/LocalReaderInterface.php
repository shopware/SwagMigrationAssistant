<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ReaderInterface;

interface LocalReaderInterface extends ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool;
}
