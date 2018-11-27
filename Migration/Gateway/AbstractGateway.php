<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Gateway;

use SwagMigrationNext\Migration\MigrationContext;

abstract class AbstractGateway implements GatewayInterface
{
    /**
     * @var MigrationContext
     */
    protected $migrationContext;

    public function __construct(MigrationContext $migrationContext)
    {
        $this->migrationContext = $migrationContext;
    }
}
