<?php declare(strict_types=1);

namespace SwagMigrationNext;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagMigrationNext extends Plugin
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
        $loader->load('shopware55.xml');
        $loader->load('writer.xml');
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
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $now = (new DateTime())->format(Defaults::DATE_FORMAT);

        $connection->beginTransaction();
        try {
            $connection->insert('swag_migration_profile', [
                'id' => Uuid::uuid4()->getBytes(),
                'name' => Shopware55Profile::PROFILE_NAME,
                'gateway_name' => Shopware55ApiGateway::GATEWAY_NAME,
                'created_at' => $now,
            ]);
            $connection->insert('swag_migration_profile', [
                'id' => Uuid::uuid4()->getBytes(),
                'name' => Shopware55Profile::PROFILE_NAME,
                'gateway_name' => Shopware55LocalGateway::GATEWAY_NAME,
                'created_at' => $now,
            ]);
            $connection->insert('swag_migration_general_setting', [
                'id' => Uuid::uuid4()->getBytes(),
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

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->exec('
DROP TABLE IF EXISTS swag_migration_general_setting;
DROP TABLE IF EXISTS swag_migration_data;
DROP TABLE IF EXISTS swag_migration_media_file;
DROP TABLE IF EXISTS swag_migration_run;
DROP TABLE IF EXISTS swag_migration_mapping;
DROP TABLE IF EXISTS swag_migration_connection;
DROP TABLE IF EXISTS swag_migration_profile;
DROP TABLE IF EXISTS swag_migration_logging;
');

        parent::uninstall($context);
    }
}
