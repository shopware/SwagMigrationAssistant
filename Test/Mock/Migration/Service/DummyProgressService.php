<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationNext\Migration\Service\ProgressState;
use Symfony\Component\HttpFoundation\Request;

class DummyProgressService implements MigrationProgressServiceInterface
{
    public function getProgress(Request $request, Context $context): ProgressState
    {
        return new ProgressState(false, true);
    }
}
