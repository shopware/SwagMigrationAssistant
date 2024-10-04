<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Writer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Shopware\Core\Content\Seo\SeoUrlPersister;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use SwagMigrationAssistant\Migration\Writer\SeoUrlWriter;

#[Package('services-settings')]
class SeoUrlWriterTest extends TestCase
{
    private EntityWriterInterface $entityWriter;

    private EntityDefinition $entityDefinition;

    /**
     * @var MockObject|EntityRepository<SalesChannelCollection>
     */
    private EntityRepository&MockObject $salesChannelRepository;

    private SeoUrlPersister&MockObject $seoUrlPersister;

    private SeoUrlWriter $seoUrlWriter;
    private const SALES_CHANNEL_ID = 'sales-channel-uuid';

    protected function setUp(): void
    {
        $this->entityWriter = $this->createMock(EntityWriterInterface::class);
        $this->entityDefinition = new SeoUrlDefinition();
        $this->salesChannelRepository = $this->createMock(EntityRepository::class);
        $this->seoUrlPersister = $this->createMock(SeoUrlPersister::class);

        $this->seoUrlWriter = new SeoUrlWriter(
            $this->entityWriter,
            $this->entityDefinition,
            $this->salesChannelRepository,
            $this->seoUrlPersister
        );
    }

    public function testWriteData(): void
    {
        $context = Context::createDefaultContext();
        $seoUrlData = [
            [
                'routeName' => 'frontend.detail.page',
                'foreignKey' => 'product-uuid',
                'salesChannelId' => self::SALES_CHANNEL_ID,
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'seoPathInfo' => 'new-seo-url',
                'isModified' => true,
                'isDeleted' => false,
            ],
        ];

        $salesChannelSearchResult = $this->getSalesChannelSearchResult($context);

        $this->salesChannelRepository->expects(static::once())
            ->method('search')
            ->willReturn($salesChannelSearchResult);

        $this->seoUrlPersister->expects(static::once())
            ->method('updateSeoUrls');

        $result = $this->seoUrlWriter->writeData($seoUrlData, $context);
        static::assertArrayHasKey(SeoUrlDefinition::ENTITY_NAME, $result);
        static::assertCount(1, $result[SeoUrlDefinition::ENTITY_NAME]);
        static::assertInstanceOf(EntityWriteResult::class, $result[SeoUrlDefinition::ENTITY_NAME][0]);
    }

    public function testWriteDataSkipIfSalesChannelIsInvalid(): void
    {
        $context = Context::createDefaultContext();
        $seoUrlData = [
            [
                'routeName' => 'frontend.detail.page',
                'foreignKey' => 'product-uuid',
                'salesChannelId' => 'invalid-sales-channel-uuid',
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'seoPathInfo' => 'new-seo-url',
                'isModified' => true,
                'isDeleted' => false,
            ],
        ];

        $salesChannelSearchResult = $this->getSalesChannelSearchResult($context);

        $this->salesChannelRepository->expects(static::once())
            ->method('search')
            ->willReturn($salesChannelSearchResult);

        $this->seoUrlPersister->expects(static::never())
            ->method('updateSeoUrls');

        $result = $this->seoUrlWriter->writeData($seoUrlData, $context);
        static::assertEmpty($result);
    }

    /**
     * @return EntitySearchResult<SalesChannelCollection>
     */
    private function getSalesChannelSearchResult(Context $context): EntitySearchResult
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(self::SALES_CHANNEL_ID);
        $salesChannelCollection = new SalesChannelCollection([$salesChannel]);

        return new EntitySearchResult(
            SalesChannelDefinition::ENTITY_NAME,
            1,
            $salesChannelCollection,
            null,
            new Criteria(),
            $context
        );
    }
}
