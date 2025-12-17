<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\ErrorResolution\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationErrorResolutionContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @final
 *
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
class MigrationPostErrorResolutionEvent extends Event implements ShopwareEvent
{
    /**
     * @internal
     */
    public function __construct(private readonly MigrationErrorResolutionContext $errorResolutionContext)
    {
    }

    public function getErrorResolutionContext(): MigrationErrorResolutionContext
    {
        return $this->errorResolutionContext;
    }

    public function getContext(): Context
    {
        return $this->errorResolutionContext->getContext();
    }
}
