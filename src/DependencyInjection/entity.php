<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use SwagMigrationAssistant\Core\Field\AnyJsonFieldSerializer;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\ErrorResolution\Entity\SwagMigrationFixDefinition;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;
use SwagMigrationAssistant\Migration\Run\MigrationProgressFieldSerializer;
use SwagMigrationAssistant\Migration\Run\PremappingFieldSerializer;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(SwagMigrationLoggingDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_logging']);

    $services->set(SwagMigrationRunDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_run']);

    $services->set(MigrationProgressFieldSerializer::class)
        ->args([
            service('validator'),
            service(DefinitionInstanceRegistry::class),
        ])
        ->tag('shopware.field_serializer');

    $services->set(PremappingFieldSerializer::class)
        ->args([
            service('validator'),
            service(DefinitionInstanceRegistry::class),
        ])
        ->tag('shopware.field_serializer');

    $services->set(SwagMigrationDataDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_data']);

    $services->set(SwagMigrationMappingDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_mapping']);

    $services->set(SwagMigrationMediaFileDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_media_file']);

    $services->set(GeneralSettingDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_general_setting']);

    $services->set(SwagMigrationConnectionDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_connection']);

    $services->set(SwagMigrationFixDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => 'swag_migration_fix']);

    $services->set(AnyJsonFieldSerializer::class)
        ->args([
            service('validator'),
            service(DefinitionInstanceRegistry::class),
        ])
        ->tag('shopware.field_serializer');
};
