<?php declare(strict_types=1);

namespace SwagMigrationNext;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
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
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $installContext): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $sql = file_get_contents($this->getPath() . '/schema.sql');
        $connection->query($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext)
    {
        $profileRepo = $this->container->get('swag_migration_profile.repository');

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $createData = [
            [
                'profile' => Shopware55Profile::PROFILE_NAME,
                'gatewayType' => Shopware55ApiGateway::GATEWAY_TYPE,
            ],
            [
                'profile' => Shopware55Profile::PROFILE_NAME,
                'gatewayType' => Shopware55LocalGateway::GATEWAY_TYPE,
            ],
        ];
        $profileRepo->create($createData, $context);
        parent::activate($activateContext);
    }
}
