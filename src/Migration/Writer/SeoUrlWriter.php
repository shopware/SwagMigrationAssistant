<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Content\Seo\SeoUrlPersister;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('fundamentals@after-sales')]
class SeoUrlWriter extends AbstractWriter
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        EntityWriterInterface $entityWriter,
        EntityDefinition $definition,
        private readonly EntityRepository $salesChannelRepository,
        private readonly SeoUrlPersister $seoUrlPersister,
    ) {
        parent::__construct($entityWriter, $definition);
    }

    public function supports(): string
    {
        return DefaultEntities::SEO_URL;
    }

    /**
     * @param array<array<string, string|bool>> $data
     *
     * @return array<string, array<EntityWriteResult>>
     */
    public function writeData(array $data, Context $context): array
    {
        $writeResults = [];

        $context->addExtension(
            self::EXTENSION_NAME,
            new ArrayStruct([
                self::EXTENSION_SOURCE_KEY => self::EXTENSION_SOURCE_VALUE,
            ]),
        );

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data, &$writeResults): void {
            $criteria = new Criteria();
            $criteria->addFilter(new NandFilter([new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_API)]));

            $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

            foreach ($data as $seoUrl) {
                $id = Uuid::randomHex();
                $seoUrl['id'] = $id;
                $salesChannel = $salesChannels->get((string) $seoUrl['salesChannelId']);

                if ($salesChannel === null) {
                    continue;
                }

                $this->seoUrlPersister->updateSeoUrls(
                    $context,
                    (string) $seoUrl['routeName'],
                    [(string) $seoUrl['foreignKey']],
                    [$seoUrl],
                    $salesChannel,
                );

                $entityDefinitionName = $this->definition->getEntityName();
                $writeResults[$entityDefinitionName][] = new EntityWriteResult(
                    $id,
                    $seoUrl,
                    $entityDefinitionName,
                    EntityWriteResult::OPERATION_INSERT
                );
            }
        });

        return $writeResults;
    }
}
