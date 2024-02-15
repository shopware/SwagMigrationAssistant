<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ThemeAssignMessage;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
#[Package('services-settings')]
/**
 * @internal
 */
final class ThemeAssignHandler
{
    public function __construct(
        private readonly RunServiceInterface $runService
    ) {
    }

    public function __invoke(ThemeAssignMessage $message): void
    {
        $this->runService->assignThemeToSalesChannel($message->getRunUuid(), $message->getContext());
    }
}
