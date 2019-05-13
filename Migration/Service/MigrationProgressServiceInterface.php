<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;

interface MigrationProgressServiceInterface
{
    public function getProgress(Request $request, Context $context): ProgressState;
}
