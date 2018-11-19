<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationNext\Migration\Service\ProgressState;

class DummyProgressService implements MigrationProgressServiceInterface
{
    public function getProgress(Context $context): ProgressState
    {
        return new ProgressState(false);
    }
}
