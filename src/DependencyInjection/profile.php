<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ProfileRegistry::class, ProfileRegistry::class)
        ->args([tagged_iterator('shopware.migration.profile')]);
};
