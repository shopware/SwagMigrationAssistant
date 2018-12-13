<?php declare(strict_types=1);

namespace SwagMigrationNext\Command\Event;

use Symfony\Component\EventDispatcher\Event;

class MigrationAssetDownloadAdvanceEvent extends Event
{
    public const EVENT_NAME = 'migration.asset.download.advance';
}
