<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\DevOps\StaticAnalyze\StaticAnalyzeKernel;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\TestBootstrapper;

require __DIR__ . '/../../../../src/Core/TestBootstrapper.php';

$bootstrapper = new TestBootstrapper();
$_SERVER['PROJECT_ROOT'] = $_ENV['PROJECT_ROOT'] = $bootstrapper->getProjectDir();
if (!defined('TEST_PROJECT_DIR')) {
    define('TEST_PROJECT_DIR', $_SERVER['PROJECT_ROOT']);
}

$classLoader = $bootstrapper->getClassLoader();
$classLoader->addPsr4('SwagMigrationAssistant\\', dirname(__DIR__));

$plugins = [
    [
        'name' => 'SwagMigrationAssistant',
        'baseClass' => 'SwagMigrationAssistant\SwagMigrationAssistant',
        'active' => true,
        'path' => 'custom/plugins/SwagMigrationAssistant',
        'version' => 'dev-master',
        'autoload' => ['psr-4' => ['SwagMigrationAssistant\\' => 'src/']],
        'managedByComposer' => false,
        'composerName' => 'swag/migration-assistant',
    ],
];

try {
    $bootstrapper->addActivePlugins('SwagMigrationAssistant');
    $bootstrapper->addCallingPlugin();
    $bootstrapper->bootstrap();
} catch (Throwable $e) {
}

$pluginLoader = new StaticKernelPluginLoader($classLoader, null, $plugins);

KernelFactory::$kernelClass = StaticAnalyzeKernel::class;

/** @var StaticAnalyzeKernel $kernel */
$kernel = KernelFactory::create('swag_migration_assistant_phpstan', true, $classLoader, $pluginLoader);

$kernel->boot();

return $kernel;
