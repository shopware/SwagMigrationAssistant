<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration;

use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

interface MigrationContextFactoryInterface
{
    public function create(SwagMigrationRunEntity $run, int $offset = 0, int $limit = 0, string $entity = ''): MigrationContextInterface;

    public function createByProfileName(string $profileName): MigrationContextInterface;

    public function createByConnection(SwagMigrationConnectionEntity $connection): MigrationContextInterface;
}
