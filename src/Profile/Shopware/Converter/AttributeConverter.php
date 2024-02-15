<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class AttributeConverter extends Converter
{
    protected string $connectionId;

    protected string $connectionName;

    public function getSourceIdentifier(array $data): string
    {
        return $data['name'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $converted = [];

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        $this->connectionName = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
            $this->connectionName = $connection->getName();
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOM_FIELD_SET,
            $this->getCustomFieldEntityName() . 'CustomFieldSet',
            $context
        );
        $converted['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $connectionName = $this->connectionName;
        $connectionName = \str_replace(' ', '', $connectionName);
        $connectionName = \preg_replace('/[^A-Za-z0-9\-]/', '', $connectionName);

        $converted['name'] = 'migration_' . $connectionName . '_' . $this->getCustomFieldEntityName();
        $converted['config'] = [
            'label' => [
                $data['_locale'] => \ucfirst($this->getCustomFieldEntityName()) . ' migration custom fields (attributes)',
            ],
            'translated' => true,
        ];
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOM_FIELD_SET_RELATION,
            $this->getCustomFieldEntityName() . 'CustomFieldSetRelation',
            $context
        );
        $this->mappingIds[] = $mapping['id'];

        $converted['relations'] = [
            [
                'id' => $mapping['entityUuid'],
                'entityName' => $this->getCustomFieldEntityName(),
            ],
        ];

        $additionalData = [];
        if (isset($data['configuration']['column_type'])) {
            $additionalData['columnType'] = $data['configuration']['column_type'];
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            $this->getCustomFieldEntityName(),
            $data['name'],
            $context,
            $this->checksum,
            $additionalData
        );

        $converted['customFields'] = [
            [
                'id' => $this->mainMapping['entityUuid'],
                'name' => $converted['name'] . '_' . $data['name'],
                'type' => $this->getCustomFieldType($data),
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

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $data, $this->mainMapping['id'] ?? null);
    }

    abstract protected function getCustomFieldEntityName(): string;

    protected function getCustomFieldConfiguration(array $data): array
    {
        $locale = (string) \str_replace('_', '-', $data['_locale']);

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

        if ($data['type'] === 'int') {
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

    protected function getConfiguredCustomFieldData(array $data, string $locale): array
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
            } else {
                $attributeData['label'] = [
                    $locale => $data['configuration']['column_name'],
                ];
            }

            if ($data['configuration']['help_text'] !== '') {
                $attributeData['helpText'] = [
                    $locale => $data['configuration']['help_text'],
                ];
            }
        }

        if ($data['configuration']['position']) {
            $attributeData['customFieldPosition'] = (int) $data['configuration']['position'];
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
            foreach (\json_decode($data['configuration']['array_store'], true, 512, \JSON_THROW_ON_ERROR) as $keyValue) {
                $options[] = [
                    'value' => $keyValue['key'],
                    'label' => [
                        $locale => $keyValue['value'],
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

    private function getCustomFieldType(array $data): string
    {
        if (isset($data['configuration'])) {
            switch ($data['configuration']['column_type']) {
                case 'integer':
                    return 'int';
                case 'float':
                    return 'float';
                case 'html':
                    return 'html';
                case 'boolean':
                    return 'bool';
                case 'date':
                case 'datetime':
                    return 'datetime';
                case 'combobox':
                    return 'select';
                default:
                    return 'text';
            }
        } else {
            switch ($data['type']) {
                case 'int':
                    return 'int';
                case 'float':
                    return 'float';
                default:
                    return 'text';
            }
        }
    }
}
