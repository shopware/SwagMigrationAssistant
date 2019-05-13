<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

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
