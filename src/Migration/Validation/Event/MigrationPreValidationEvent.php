<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Validation\MigrationValidationContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @final
 *
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
class MigrationPreValidationEvent extends Event implements ShopwareEvent
{
    /**
     * @internal
     */
    public function __construct(private readonly MigrationValidationContext $validationContext)
    {
    }

    public function getValidationContext(): MigrationValidationContext
    {
        return $this->validationContext;
    }

    public function getContext(): Context
    {
        return $this->validationContext->getContext();
    }
}
