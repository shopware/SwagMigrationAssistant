<?php declare(strict_types=1);

namespace SwagMigrationNext\Command\Event;

use Symfony\Component\EventDispatcher\Event;

class MigrationAssetDownloadAdvanceEvent extends Event
{
    public const EVENT_NAME = 'migration.asset.download.advance';

    /**
     * @var string
     */
    private $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
