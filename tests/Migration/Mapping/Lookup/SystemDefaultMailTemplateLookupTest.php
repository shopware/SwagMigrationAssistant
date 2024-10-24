<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemDefaultMailTemplateLookup;

class SystemDefaultMailTemplateLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $typeId, ?string $expectedResult): void
    {
        $systemDefaultMailTemplateLookup = $this->getSystemDefaultMailTemplateLookup();

        static::assertSame($expectedResult, $systemDefaultMailTemplateLookup->get($typeId, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $typeId, ?string $expectedResult): void
    {
        $systemDefaultMailTemplateLookup = $this->getMockedSystemDefaultMailTemplateLookup();

        static::assertSame($expectedResult, $systemDefaultMailTemplateLookup->get($typeId, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $systemDefaultMailTemplateLookup = $this->getMockedSystemDefaultMailTemplateLookup();

        $cacheProperty = new \ReflectionProperty(SystemDefaultMailTemplateLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($systemDefaultMailTemplateLookup));

        $systemDefaultMailTemplateLookup->reset();

        static::assertEmpty($cacheProperty->getValue($systemDefaultMailTemplateLookup));
    }

    /**
     * @return array<int, array{typeId: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['typeId' => Uuid::randomHex(), 'expectedResult' => null];
        $returnData[] = ['typeId' => Uuid::randomHex(), 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{typeId: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $list = self::getContainer()->get('mail_template.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $mailTemplateType) {
            static::assertInstanceOf(MailTemplateEntity::class, $mailTemplateType);
            if ($mailTemplateType->getMailTemplateTypeId() === null) {
                continue;
            }
            $returnData[] = ['typeId' => $mailTemplateType->getMailTemplateTypeId(), 'expectedResult' => $mailTemplateType->getId()];
        }

        return $returnData;
    }

    private function getSystemDefaultMailTemplateLookup(): SystemDefaultMailTemplateLookup
    {
        return $this->getContainer()->get(SystemDefaultMailTemplateLookup::class);
    }

    private function getMockedSystemDefaultMailTemplateLookup(): SystemDefaultMailTemplateLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('SystemDefaultMailTemplateLookup repository should not be called'));

        $systemDefaultMailTemplateLookup = new SystemDefaultMailTemplateLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(SystemDefaultMailTemplateLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['typeId']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($systemDefaultMailTemplateLookup, $cache);

        return $systemDefaultMailTemplateLookup;
    }
}
