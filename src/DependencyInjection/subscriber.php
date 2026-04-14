<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\Run\RunTransitionService;
use SwagMigrationAssistant\Migration\Subscriber\MediaDeletedSubscriber;
use SwagMigrationAssistant\Migration\Subscriber\MessageQueueSubscriber;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(MediaDeletedSubscriber::class)
        ->args([service('swag_migration_media_file.repository')])
        ->tag('kernel.event_subscriber');

    $services->set(MessageQueueSubscriber::class)
        ->args([
            service('messenger.default_bus'),
            service('swag_migration_run.repository'),
            service(LoggingService::class),
            service(RunTransitionService::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('kernel.event_subscriber');
};
