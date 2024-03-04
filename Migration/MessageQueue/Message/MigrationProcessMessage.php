<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\MessageQueue\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

class MigrationProcessMessage implements AsyncMessageInterface
{
    public function __construct(private readonly Context $context, private readonly string $runUuid)
    {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getRunUuid(): string
    {
        return $this->runUuid;
    }
}
