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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

#[Package('services-settings')]
class SwagMigrationAssistant extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('entity.xml');
        $loader->load('gateway.xml');
        $loader->load('migration.xml');
        $loader->load('profile.xml');
        $loader->load('shopware.xml');
        $loader->load('shopware54.xml');
        $loader->load('shopware55.xml');
        $loader->load('shopware56.xml');
        $loader->load('shopware57.xml');
        $loader->load('shopware6.xml');
        $loader->load('subscriber.xml');
        $loader->load('writer.xml');
        $loader->load('dataProvider.xml');
    }

    public function rebuildContainer(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationNamespace(): string
    {
        return $this->getNamespace() . '\Core\Migration';
    }

    /**
     * {@inheritdoc}
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
            $connection->insert('swag_migration_general_setting', [
                'id' => Uuid::randomBytes(),
                'created_at' => $now,
            ]);

            $connection->commit();
        } catch (DBALException $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        if ($context->keepUserData()) {
            parent::uninstall($context);

            return;
        }

        if ($this->container === null) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('
DROP TABLE IF EXISTS swag_migration_general_setting;
DROP TABLE IF EXISTS swag_migration_data;
DROP TABLE IF EXISTS swag_migration_mapping;
DROP TABLE IF EXISTS swag_migration_logging;
DROP TABLE IF EXISTS swag_migration_media_file;
DROP TABLE IF EXISTS swag_migration_run;
DROP TABLE IF EXISTS swag_migration_connection;
');

        parent::uninstall($context);
    }
}
