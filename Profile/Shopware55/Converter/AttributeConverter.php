<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;

abstract class AttributeConverter extends AbstractConverter
{
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    public function __construct(MappingServiceInterface $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $converted = [];

        $converted['id'] = $this->mappingService->createNewUuid(
            $migrationContext->getConnection()->getId(),
            'attributeSet',
            $this->getAttributeEntityName() . 'AttributeSet',
            $context
        );

        $converted['name'] = $this->getAttributeEntityName() . '_migration_' . $migrationContext->getConnection()->getName();
        $converted['config'] = [
            'label' => [
                $data['_locale'] => ucfirst($this->getAttributeEntityName()) . ' migration attributes',
            ],
            'translated' => true,
        ];
        $converted['relations'] = [
            [
                'id' => $this->mappingService->createNewUuid(
                    $migrationContext->getConnection()->getId(),
                    'attributeSetRelation',
                    $this->getAttributeEntityName() . 'AttributeSetRelation',
                    $context
                ),
                'entityName' => $this->getAttributeEntityName(),
            ],
        ];

        $converted['attributes'] = [
            [
                'id' => $this->mappingService->createNewUuid(
                    $migrationContext->getConnection()->getId(),
                    $this->getSupportedEntityName(),
                    $data['name'],
                    $context
                ),
                'name' => $this->getAttributeEntityName() . '_' . $data['name'],
                'type' => $data['type'],
                'config' => $this->getAttributeConfiguration($data),
            ],
        ];

        unset(
            $data['name'],
            $data['type'],
            $data['configuration'],
            $data['_locale']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    abstract protected function getAttributeEntityName(): string;

    protected function getAttributeConfiguration(array $data): array
    {
        $locale = str_replace('_', '-', $data['_locale']);

        if (isset($data['configuration'])) {
            return $this->getConfiguredAttributeData($data, $locale);
        }

        $attributeData = [
            'componentName' => 'sw-field',
            'label' => [
                $locale => $data['name'],
            ],
        ];

        if ($data['type'] === 'text') {
            $attributeData['type'] = 'text';
            $attributeData['attributeType'] = 'text';

            return $attributeData;
        }

        if ($data['type'] === 'integer') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'int';
            $attributeData['attributeType'] = 'number';

            return $attributeData;
        }

        if ($data['type'] === 'float') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'float';
            $attributeData['attributeType'] = 'number';

            return $attributeData;
        }

        return [];
    }

    private function getConfiguredAttributeData(array $data, string $locale): array
    {
        $attributeData = ['componentName' => 'sw-field'];

        if (isset($data['configuration']['translations'])) {
            foreach ($data['configuration']['translations'] as $attributeField => $translations) {
                $attributeData[$attributeField] = $translations;
            }
        } else {
            $attributeData['label'] = [
                $locale => $data['configuration']['label'],
            ];
            $attributeData['helpText'] = [
                $locale => $data['configuration']['help_text'],
            ];
            $attributeData['tooltipText'] = [
                $locale => $data['configuration']['support_text'],
            ];
        }

        if ($data['configuration']['column_type'] === 'text' || $data['configuration']['column_type'] === 'string') {
            $attributeData['type'] = 'text';
            $attributeData['attributeType'] = 'text';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'integer') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'int';
            $attributeData['attributeType'] = 'number';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'float') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'float';
            $attributeData['attributeType'] = 'number';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'html') {
            $attributeData['componentName'] = 'sw-text-editor';
            $attributeData['attributeType'] = 'textEditor';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'boolean') {
            $attributeData['type'] = 'checkbox';
            $attributeData['attributeType'] = 'checkbox';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'date') {
            $attributeData['type'] = 'date';
            $attributeData['dateType'] = 'date';
            $attributeData['attributeType'] = 'date';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'datetime') {
            $attributeData['type'] = 'date';
            $attributeData['dateType'] = 'datetime';
            $attributeData['attributeType'] = 'date';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'combobox') {
            $options = [];
            foreach (json_decode($data['configuration']['array_store']) as $keyValue) {
                $options[] = [
                    'id' => $keyValue->key,
                    'name' => [
                        $locale => $keyValue->value,
                    ],
                ];
            }

            $attributeData['componentName'] = 'sw-select';
            $attributeData['type'] = 'select';
            $attributeData['attributeType'] = 'select';
            $attributeData['options'] = $options;

            return $attributeData;
        }

        return [];
    }
}
