<?php declare(strict_types=1);

namespace SwagMigrationNext;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagMigrationNext extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('gateway.xml');
        $loader->load('migration.xml');
        $loader->load('profile.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        $sql = file_get_contents($this->getPath() . '/schema.sql');

        $this->container->get(Connection::class)->query($sql);
    }
}
