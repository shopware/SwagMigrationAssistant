<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class ShippingMethodConverter extends ShopwareMediaConverter
{
    /**
     * @param EntityRepository<ShippingMethodCollection> $shippingMethodRepository
     */
    public function __construct(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService,
        protected EntityRepository $shippingMethodRepository,
    ) {
        parent::__construct($mappingService, $loggingService, $mediaFileService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === ShippingMethodDataSet::getEntity();
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $manufacturer) {
            if (isset($manufacturer['media']['id'])) {
                $mediaIds[] = $manufacturer['media']['id'];
            }
        }

        return $mediaIds;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SHIPPING_METHOD,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::NUMBER_RANGE
        );

        if (isset($converted['prices'])) {
            foreach ($converted['prices'] as &$price) {
                $this->updateAssociationIds(
                    $price['currencyPrice'],
                    DefaultEntities::CURRENCY,
                    'currencyId',
                    DefaultEntities::PRODUCT
                );
            }
            unset($price);
        }

        if (isset($data['taxId'])) {
            $converted['taxId'] = $this->getMappingIdFacade(DefaultEntities::TAX, $data['taxId']);
        }

        if (isset($data['deliveryTimeId'])) {
            $converted['deliveryTimeId'] = $this->getMappingIdFacade(DefaultEntities::DELIVERY_TIME, $data['deliveryTimeId']);
        }

        if (isset($converted['media'])) {
            $this->updateMediaAssociation($converted['media']);
        }

        if (isset($converted['technicalName'])) {
            $this->updateTechnicalNameIfNecessary($converted);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $converted
     */
    private function updateTechnicalNameIfNecessary(array &$converted): void
    {
        if (!$this->hasTechnicalNameColumn()) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $converted['technicalName']));
        $existingCount = $this->shippingMethodRepository->searchIds($criteria, $this->context)->getTotal();

        if ($existingCount === 0) {
            return;
        }

        $converted['technicalName'] .= '_migrated';
    }

    private function hasTechnicalNameColumn(): bool
    {
        return $this->shippingMethodRepository->getDefinition()->getField('technicalName') !== null;
    }
}
