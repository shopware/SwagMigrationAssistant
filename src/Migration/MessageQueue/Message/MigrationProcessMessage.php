<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('services-settings')]
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
