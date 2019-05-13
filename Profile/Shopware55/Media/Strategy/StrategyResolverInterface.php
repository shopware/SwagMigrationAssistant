<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Media\Strategy;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface StrategyResolverInterface
{
    public function supports(string $path, MigrationContextInterface $migrationContext): bool;

    public function resolve(string $path, MigrationContextInterface $migrationContext): string;
}
