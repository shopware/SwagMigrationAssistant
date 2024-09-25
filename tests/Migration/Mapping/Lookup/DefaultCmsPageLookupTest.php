<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Mapping\Lookup;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DefaultCmsPageLookup;

class DefaultCmsPageLookupTest extends TestCase
{
    use KernelTestBehaviour;

    public function testGet(): void
    {
        $defaultCmsPageLookup = new DefaultCmsPageLookup(
            $this->getContainer()->get('cms_page.repository')
        );

        $result = $defaultCmsPageLookup->get(Context::createDefaultContext());

        static::assertSame($this->getDefaultCmsPageId(), $result);
    }

    public function testShouldReturnNull(): void
    {
        $cmsPageRepository = $this->createMock(EntityRepository::class);
        $cmsPageRepository->method('search')->willReturn(
            new EntitySearchResult(
                CmsPageEntity::class,
                0,
                new CmsPageCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $defaultCmsPageLookup = new DefaultCmsPageLookup($cmsPageRepository);

        $result = $defaultCmsPageLookup->get(Context::createDefaultContext());

        static::assertNull($result);
    }

    public function testGetShouldGetDataFromCache(): void
    {
        $cmsPageRepository = $this->createMock(EntityRepository::class);
        $cmsPageRepository->method('search')->willThrowException(
            new \Exception('DefaultCmsPage repository Should not be called')
        );

        $defaultCmsPageLookup = new DefaultCmsPageLookup($cmsPageRepository);

        $reflectionProperty = new \ReflectionProperty(DefaultCmsPageLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($defaultCmsPageLookup, $this->getDefaultCmsPageId());

        $result = $defaultCmsPageLookup->get(Context::createDefaultContext());

        static::assertSame($this->getDefaultCmsPageId(), $result);
    }

    public function testReset(): void
    {
        $defaultCmsPageLookup = new DefaultCmsPageLookup(
            $this->getContainer()->get('cms_page.repository')
        );

        $reflectionProperty = new \ReflectionProperty(DefaultCmsPageLookup::class, 'cache');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($defaultCmsPageLookup, $this->getDefaultCmsPageId());

        static::assertNotEmpty($reflectionProperty->getValue($defaultCmsPageLookup));

        $defaultCmsPageLookup->reset();

        static::assertEmpty($reflectionProperty->getValue($defaultCmsPageLookup));
    }

    private function getDefaultCmsPageId(): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', 'product_list'));
        $criteria->addFilter(new EqualsFilter('locked', true));

        $result = $this->getContainer()->get('cms_page.repository')->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertInstanceOf(CmsPageEntity::class, $result);
        static::assertTrue(Uuid::isValid($result->getId()));

        return $result->getId();
    }
}
