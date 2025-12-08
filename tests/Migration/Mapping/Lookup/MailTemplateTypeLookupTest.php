<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MailTemplateTypeLookup;

class MailTemplateTypeLookupTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('getData')]
    public function testGet(string $technicalName, ?string $expectedResult): void
    {
        $mailTemplateTypeLookup = $this->getMailTemplateTypeLookup();

        static::assertSame($expectedResult, $mailTemplateTypeLookup->get($technicalName, Context::createDefaultContext()));
    }

    #[DataProvider('getDatabaseData')]
    public function testGetShouldGetDataFromCache(string $technicalName, ?string $expectedResult): void
    {
        $mailTemplateTypeLookup = $this->getMockedMailTemplateTypeLookup();

        static::assertSame($expectedResult, $mailTemplateTypeLookup->get($technicalName, Context::createDefaultContext()));
    }

    public function testReset(): void
    {
        $mailTemplateTypeLookup = $this->getMockedMailTemplateTypeLookup();

        $cacheProperty = new \ReflectionProperty(MailTemplateTypeLookup::class, 'cache');
        $cacheProperty->setAccessible(true);

        static::assertNotEmpty($cacheProperty->getValue($mailTemplateTypeLookup));

        $mailTemplateTypeLookup->reset();

        static::assertEmpty($cacheProperty->getValue($mailTemplateTypeLookup));
    }

    /**
     * @return array<int, array{technicalName: string, expectedResult: string|null}>
     */
    public static function getData(): array
    {
        $returnData = self::getDatabaseData();
        $returnData[] = ['technicalName' => 'Foo', 'expectedResult' => null];
        $returnData[] = ['technicalName' => 'Bar', 'expectedResult' => null];

        return $returnData;
    }

    /**
     * @return array<int, array{technicalName: string, expectedResult: string}>
     */
    public static function getDatabaseData(): array
    {
        $criteria = new Criteria();
        $list = self::getContainer()->get('mail_template_type.repository')->search($criteria, Context::createDefaultContext());

        $returnData = [];
        foreach ($list as $mailTemplateType) {
            static::assertInstanceOf(MailTemplateTypeEntity::class, $mailTemplateType);
            $returnData[] = ['technicalName' => $mailTemplateType->getTechnicalName(), 'expectedResult' => $mailTemplateType->getId()];
        }

        return $returnData;
    }

    private function getMailTemplateTypeLookup(): MailTemplateTypeLookup
    {
        return static::getContainer()->get(MailTemplateTypeLookup::class);
    }

    private function getMockedMailTemplateTypeLookup(): MailTemplateTypeLookup
    {
        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('searchIds')->willThrowException(new \Exception('MailTemplateTypeLookup repository should not be called'));

        $mailTemplateTypeLookup = new MailTemplateTypeLookup($entityRepository);

        $reflectionProperty = new \ReflectionProperty(MailTemplateTypeLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $databaseData = self::getDatabaseData();

        $cache = [];
        foreach ($databaseData as $data) {
            $cache[$data['technicalName']] = $data['expectedResult'];
        }

        $reflectionProperty->setValue($mailTemplateTypeLookup, $cache);

        return $mailTemplateTypeLookup;
    }
}
