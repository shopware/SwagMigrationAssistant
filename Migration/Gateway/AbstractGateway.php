<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Gateway;

use SwagMigrationNext\Migration\MigrationContextInterface;

abstract class AbstractGateway implements GatewayInterface
{
    /**
     * @var MigrationContextInterface
     */
    protected $migrationContext;

    public function __construct(MigrationContextInterface $migrationContext)
    {
        $this->migrationContext = $migrationContext;
    }
}
