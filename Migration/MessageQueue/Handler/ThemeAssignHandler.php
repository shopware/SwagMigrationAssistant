<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ThemeAssignMessage;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
#[Package('services-settings')]
class ThemeAssignHandler
{
    public function __construct(
        private readonly RunServiceInterface $runService
    )
    {
    }

    public function __invoke(ThemeAssignMessage $message): void
    {
        $this->runService->assignThemeToSalesChannel($message->getRunUuid(), $message->getContext());
    }
}
