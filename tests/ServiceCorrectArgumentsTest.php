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
use Symfony\Component\Finder\Finder;

#[Package('fundamentals@after-sales')]
class ServiceCorrectArgumentsTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('serviceProvider')]
    public function testServiceShouldHaveCorrectArgumentsInContainer(string $xmlPath, string $serviceId): void
    {
        $service = static::getContainer($serviceId);
        static::assertNotNull($service);
    }

    /**
     * @return array<string, array{ xmlPath: string, serviceId: string }>
     */
    public static function serviceProvider(): array
    {
        $pluginPath = __DIR__ . '/../';
        $finder = new Finder();
        $finder->in($pluginPath)->files()->name('*.xml')->contains('<services>');

        $testCases = [];
        foreach ($finder->getIterator() as $xmlFile) {
            $xmlPath = $xmlFile->getRealPath();
            if (!$xmlPath) {
                continue;
            }

            $services = self::getServicesFromXml($xmlPath);

            foreach ($services as $serviceId) {
                $testCases[$serviceId] = [
                    'xmlPath' => $xmlPath,
                    'serviceId' => $serviceId,
                ];
            }
        }

        return $testCases;
    }

    /**
     * @return string[]
     */
    private static function getServicesFromXml(string $xmlPath): array
    {
        $xmlContent = file_get_contents($xmlPath);
        static::assertNotFalse($xmlContent);
        $document = new \DOMDocument();
        $document->loadXML($xmlContent);

        $serviceTags = $document->getElementsByTagName('service');

        $serviceIds = [];
        foreach ($serviceTags as $element) {
            if ($element instanceof \DOMElement) {
                $id = $element->getAttribute('id');
                $abstract = $element->getAttribute('abstract');

                if (\strtolower($abstract) === 'true') {
                    // skipping abstract services,
                    // because objects of them can't be constructed
                    continue;
                }

                $serviceIds[] = $id;
            }
        }

        return $serviceIds;
    }
}
