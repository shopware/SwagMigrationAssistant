<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Media\Strategy;

use SwagMigrationNext\Migration\MigrationContext;

interface StrategyResolverInterface
{
    public function supports(string $path, MigrationContext $migrationContext): bool;

    public function resolve(string $path, MigrationContext $migrationContext): string;
}
