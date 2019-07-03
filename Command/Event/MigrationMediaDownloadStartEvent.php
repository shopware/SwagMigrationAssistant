<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Command\Event;

use Symfony\Component\EventDispatcher\Event;

class MigrationMediaDownloadStartEvent extends Event
{
    /**
     * @var int
     */
    private $numberOfFiles;

    public function __construct(int $numberOfFiles = 0)
    {
        $this->numberOfFiles = $numberOfFiles;
    }

    public function getNumberOfFiles(): int
    {
        return $this->numberOfFiles;
    }
}
