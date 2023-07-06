<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\DriverManager;
use Shopware\Core\DevOps\StaticAnalyze\StaticAnalyzeKernel;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\TestBootstrapper;
use SwagMigrationAssistant\Test\Shopware5DatabaseConnection;

require __DIR__ . '/../../../../src/Core/TestBootstrapper.php';

/** @var Shopware\Core\TestBootstrapper $bootstrapper */
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
} catch (\Throwable $e) {
}

$pluginLoader = new StaticKernelPluginLoader($classLoader, null, $plugins);
$kernel = new StaticAnalyzeKernel('test', true, $pluginLoader, 'phpstan-test-cache-id');
$kernel->boot();

$connectionParams = [
    'dbname' => Shopware5DatabaseConnection::DB_NAME,
    'user' => Shopware5DatabaseConnection::DB_USER,
    'password' => Shopware5DatabaseConnection::DB_PASSWORD,
    'host' => Shopware5DatabaseConnection::DB_HOST,
    'port' => Shopware5DatabaseConnection::DB_PORT,
    'driver' => 'pdo_mysql',
    'charset' => 'utf8mb4',
];

$connection = DriverManager::getConnection($connectionParams);

$ping = true;

try {
    $connection->executeStatement('SELECT 1');
} catch (\Exception $e) {
    $ping = false;
}

if ($ping === false) {
    $_SERVER['SWAG_MIGRATION_ASSISTANT_SKIP_SW5_TESTS'] = 'true';
    putenv('SWAG_MIGRATION_ASSISTANT_SKIP_SW5_TESTS=true');
}

return $kernel;
