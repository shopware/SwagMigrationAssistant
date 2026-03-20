<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\SwagMigrationAssistant;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Finder\Finder;

#[Package('fundamentals@after-sales')]
class ServiceCorrectArgumentsTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('serviceProvider')]
    public function testServiceShouldHaveCorrectArgumentsInContainer(string $serviceId): void
    {
        $service = static::getContainer()->get($serviceId);

        static::assertNotNull($service);
    }

    /**
     * @return array<string, array{ serviceId: string }>
     */
    public static function serviceProvider(): array
    {
        $locator = new FileLocator(SwagMigrationAssistant::DEPENDENCY_LOCATION);

        $container = new ContainerBuilder();
        $loader = new PhpFileLoader($container, $locator);

        $finder = new Finder();
        $finder->in(SwagMigrationAssistant::DEPENDENCY_LOCATION)
            ->files()
            ->name('*.php');

        foreach ($finder as $file) {
            $loader->load($file->getFilename());
        }

        $testCases = [];

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            if ($definition->isAbstract()) {
                continue;
            }

            $testCases[$serviceId] = [
                'serviceId' => $serviceId,
            ];
        }

        return $testCases;
    }
}
