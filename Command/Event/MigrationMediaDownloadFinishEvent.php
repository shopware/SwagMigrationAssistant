<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Command\Event;

use Symfony\Component\EventDispatcher\Event;

class MigrationMediaDownloadFinishEvent extends Event
{
    /**
     * @var int
     */
    private $migrated;

    /**
     * @var int
     */
    private $skipped;

    public function __construct(int $migrated = 0, int $skipped = 0)
    {
        $this->migrated = $migrated;
        $this->skipped = $skipped;
    }

    public function getMigrated(): int
    {
        return $this->migrated;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }
}
