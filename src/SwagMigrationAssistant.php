<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\ErrorResolution\Entity\SwagMigrationFixDefinition;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

#[Package('fundamentals@after-sales')]
class SwagMigrationAssistant extends Plugin
{
    final public const MIGRATION_ENTITIES = [
        SwagMigrationDataDefinition::ENTITY_NAME,
        SwagMigrationMediaFileDefinition::ENTITY_NAME,
        SwagMigrationLoggingDefinition::ENTITY_NAME,
        SwagMigrationRunDefinition::ENTITY_NAME,
        SwagMigrationMappingDefinition::ENTITY_NAME,
        GeneralSettingDefinition::ENTITY_NAME,
        SwagMigrationFixDefinition::ENTITY_NAME,
        SwagMigrationConnectionDefinition::ENTITY_NAME,
    ];

    final public const DEPENDENCY_LOCATION = __DIR__ . '/DependencyInjection/';

    private const CORE_MIGRATION_NAMESPACE = '\Core\Migration';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $locator = new FileLocator(self::DEPENDENCY_LOCATION);

        $phpLoader = new PhpFileLoader($container, $locator);
        $phpLoader->load('shopware.php');
        $phpLoader->load('shopware54.php');
        $phpLoader->load('shopware55.php');
        $phpLoader->load('shopware56.php');
        $phpLoader->load('shopware57.php');
        $phpLoader->load('shopware6.php');
        $phpLoader->load('dataProvider.php');
        $phpLoader->load('entity.php');
        $phpLoader->load('gateway.php');
        $phpLoader->load('migration.php');
        $phpLoader->load('profile.php');
        $phpLoader->load('subscriber.php');
        $phpLoader->load('writer.php');
    }

    public function rebuildContainer(): bool
    {
        return false;
    }

    public function getMigrationNamespace(): string
    {
        return $this->getNamespace() . self::CORE_MIGRATION_NAMESPACE;
    }

    /**
     * @throws DBALException
     */
    public function postInstall(InstallContext $installContext): void
    {
        if ($this->container === null) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $now = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->beginTransaction();

        try {
            $connection->insert(GeneralSettingDefinition::ENTITY_NAME, [
                'id' => Uuid::randomBytes(),
                'created_at' => $now,
            ]);

            $connection->commit();
        } catch (DBALException $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);

            return;
        }

        if ($this->container === null) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        foreach (self::MIGRATION_ENTITIES as $table) {
            $connection->executeStatement('DROP TABLE IF EXISTS `' . $table . '`');
        }

        parent::uninstall($uninstallContext);
    }
}
