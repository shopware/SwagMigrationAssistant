<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Tax\TaxDefinition;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class ProductConverter implements ConverterInterface
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    public function __construct(MappingServiceInterface $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function convert(array $data): ConvertStruct
    {
        $converted['id'] = $this->mappingService->createNewUuid(
            ProductDefinition::getEntityName(),
            $data['product_detail']['id']
        );

        $converted['name'] = $data['product']['name'];
        unset($data['name']);

        $converted['manufacturer'] = $this->getManufacturer($data['supplier']);
        unset($data['supplier']);
        $converted['tax'] = $this->getTax($data['tax']);
        unset($data['tax']);

        $converted['price'] = $this->getPrice($data['prices'][0], $converted['tax']['taxRate']);
        $converted['priceRules'] = $this->getPriceRules($data['prices'], $converted);
        unset($data['prices']);

        return new ConvertStruct($converted, $data);
    }

    private function getManufacturer(array $manufacturerData): array
    {
        $newData['id'] = $this->mappingService->createNewUuid(
            ProductManufacturerDefinition::getEntityName(),
            $manufacturerData['id']
        );

        $this->checkValue($newData, 'name', $manufacturerData['name']);
        $this->checkValue($newData, 'link', $manufacturerData['link']);
        $this->checkValue($newData, 'description', $manufacturerData['description']);
        $this->checkValue($newData, 'metaTitle', $manufacturerData['meta_title']);
        $this->checkValue($newData, 'metaDescription', $manufacturerData['meta_description']);
        $this->checkValue($newData, 'metaKeywords', $manufacturerData['meta_keywords']);

        return $newData;
    }

    private function getTax(array $taxData): array
    {
        return [
            'id' => $this->mappingService->createNewUuid(
                TaxDefinition::getEntityName(),
                $taxData['id']
            ),
            'taxRate' => (float) $taxData['tax'],
            'name' => $taxData['description'],
        ];
    }

    private function getPrice(array $priceData, float $taxRate): array
    {
        return [
            'gross' => (float) $priceData['price']['price'] * (1 + $taxRate / 100),
            'net' => (float) $priceData['price']['price'],
        ];
    }

    private function checkValue(array &$newData, $newKey, $sourceValue): void
    {
        if ($sourceValue !== null && $sourceValue !== '') {
            $newData[$newKey] = $sourceValue;
        }
    }

    private function getPriceRules(array $priceData, array $converted): array
    {
        $newData = [];
        foreach ($priceData as $price) {
            $newData[] = [
                'productId' => $converted['id'],
                'currencyId' => Defaults::CURRENCY,
                'rule' => [
                    'id' => $this->mappingService->createNewUuid(
                        RuleDefinition::getEntityName(),
                        $price['price']['pricegroup']
                    ),
                    'name' => $price['price']['pricegroup'],
                    'priority' => 0,
                    'payload' => new AndRule(),
                ],
                'price' => $this->getPrice($price, $converted['tax']['taxRate']),
                'quantityStart' => (int) $price['price']['from'],
                'quantityEnd' => $price['price']['to'] !== 'beliebig' ? (int) $price['price']['to'] : null,
            ];
        }

        return $newData;
    }
}
