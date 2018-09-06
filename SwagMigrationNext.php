<?php declare(strict_types=1);

namespace SwagMigrationNext;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Gateway\Shopware55\Api\Shopware55ApiGateway;
use SwagMigrationNext\Gateway\Shopware55\Local\Shopware55LocalGateway;
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
        $loader->load('gateway.xml');
        $loader->load('migration.xml');
        $loader->load('profile.xml');
        $loader->load('shopware55.xml');
        $loader->load('writer.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $installContext): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $tenantId = Uuid::fromHexToBytes($installContext->getContext()->getTenantId());
        $sql = file_get_contents($this->getPath() . '/schema.sql');

        $connection->beginTransaction();
        try {
            $connection->executeUpdate($sql);
            $connection->insert('swag_migration_profile', [
                'id' => Uuid::uuid4()->getBytes(),
                'tenant_id' => $tenantId,
                'profile' => Shopware55Profile::PROFILE_NAME,
                'gateway' => Shopware55ApiGateway::GATEWAY_TYPE,
                'credential_fields' => json_encode(['endpoint' => '', 'apiUser' => '', 'apiKey' => '']),
            ]);
            $connection->insert('swag_migration_profile', [
                'id' => Uuid::uuid4()->getBytes(),
                'tenant_id' => $tenantId,
                'profile' => Shopware55Profile::PROFILE_NAME,
                'gateway' => Shopware55LocalGateway::GATEWAY_TYPE,
                'credential_fields' => json_encode(['dbHost' => '', 'dbPort' => '', 'dbName' => '', 'dbUser' => '', 'dbPassword' => '']),
            ]);
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
DROP TABLE IF EXISTS swag_migration_data;
DROP TABLE IF EXISTS swag_migration_mapping;
DROP TABLE IF EXISTS swag_migration_profile;
');

        parent::uninstall($context);
    }
}
