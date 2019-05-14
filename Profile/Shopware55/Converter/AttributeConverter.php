<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\AbstractConverter;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

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
            DefaultEntities::CUSTOM_FIELD_SET,
            $this->getCustomFieldEntityName() . 'CustomFieldSet',
            $context
        );

        $converted['name'] = $this->getCustomFieldEntityName() . '_migration_' . $migrationContext->getConnection()->getName();
        $converted['config'] = [
            'label' => [
                $data['_locale'] => ucfirst($this->getCustomFieldEntityName()) . ' migration custom fields (attributes)',
            ],
            'translated' => true,
        ];
        $converted['relations'] = [
            [
                'id' => $this->mappingService->createNewUuid(
                    $migrationContext->getConnection()->getId(),
                    DefaultEntities::CUSTOM_FIELD_SET_RELATION,
                    $this->getCustomFieldEntityName() . 'CustomFieldSetRelation',
                    $context
                ),
                'entityName' => $this->getCustomFieldEntityName(),
            ],
        ];

        $converted['customFields'] = [
            [
                'id' => $this->mappingService->createNewUuid(
                    $migrationContext->getConnection()->getId(),
                    $this->getSupportedEntityName(),
                    $data['name'],
                    $context
                ),
                'name' => $converted['name'] . '_' . $data['name'],
                'type' => $data['type'],
                'config' => $this->getCustomFieldConfiguration($data),
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

    abstract protected function getCustomFieldEntityName(): string;

    protected function getCustomFieldConfiguration(array $data): array
    {
        $locale = str_replace('_', '-', $data['_locale']);

        if (isset($data['configuration'])) {
            return $this->getConfiguredCustomFieldData($data, $locale);
        }

        $attributeData = [
            'componentName' => 'sw-field',
            'label' => [
                $locale => $data['name'],
            ],
            'helpText' => [
                $locale => null,
            ],
            'placeholder' => [
                $locale => null,
            ],
            'type' => 'text',
            'customFieldType' => 'text',
        ];

        if ($data['type'] === 'text') {
            return $attributeData;
        }

        if ($data['type'] === 'integer') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'int';
            $attributeData['customFieldType'] = 'number';

            return $attributeData;
        }

        if ($data['type'] === 'float') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'float';
            $attributeData['customFieldType'] = 'number';

            return $attributeData;
        }

        return $attributeData;
    }

    private function getConfiguredCustomFieldData(array $data, string $locale): array
    {
        $attributeData = ['componentName' => 'sw-field'];

        if (isset($data['configuration']['translations'])) {
            foreach ($data['configuration']['translations'] as $attributeField => $translations) {
                $attributeData[$attributeField] = $translations;
            }
        } else {
            if ($data['configuration']['label'] !== null && $data['configuration']['label'] !== '') {
                $attributeData['label'] = [
                    $locale => $data['configuration']['label'],
                ];
            }

            if ($data['configuration']['help_text'] !== '') {
                $attributeData['helpText'] = [
                    $locale => $data['configuration']['help_text'],
                ];
            }
        }

        if ($data['configuration']['column_type'] === 'text' || $data['configuration']['column_type'] === 'string') {
            $attributeData['type'] = 'text';
            $attributeData['customFieldType'] = 'text';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'integer') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'int';
            $attributeData['customFieldType'] = 'number';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'float') {
            $attributeData['type'] = 'number';
            $attributeData['numberType'] = 'float';
            $attributeData['customFieldType'] = 'number';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'html') {
            $attributeData['componentName'] = 'sw-text-editor';
            $attributeData['customFieldType'] = 'textEditor';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'boolean') {
            $attributeData['type'] = 'checkbox';
            $attributeData['customFieldType'] = 'checkbox';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'date') {
            $attributeData['type'] = 'date';
            $attributeData['dateType'] = 'date';
            $attributeData['customFieldType'] = 'date';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'datetime') {
            $attributeData['type'] = 'date';
            $attributeData['dateType'] = 'datetime';
            $attributeData['customFieldType'] = 'date';

            return $attributeData;
        }

        if ($data['configuration']['column_type'] === 'combobox') {
            $options = [];
            foreach (json_decode($data['configuration']['array_store']) as $keyValue) {
                $options[] = [
                    'value' => $keyValue->key,
                    'label' => [
                        $locale => $keyValue->value,
                    ],
                ];
            }

            $attributeData['componentName'] = 'sw-single-select';
            $attributeData['type'] = 'select';
            $attributeData['customFieldType'] = 'select';
            $attributeData['options'] = $options;

            return $attributeData;
        }

        return [];
    }
}
