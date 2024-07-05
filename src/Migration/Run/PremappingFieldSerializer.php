<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Json;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Package('services-settings')]
class PremappingFieldSerializer extends JsonFieldSerializer
{
    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        if (!$field instanceof PremappingField) {
            throw MigrationException::invalidSerializerField(PremappingField::class, $field);
        }

        $value = $data->getValue();

        if ($value === null) {
            yield $field->getStorageName() => null;

            return;
        }

        $this->validate([new Type('array')], $data, $parameters->getPath());

        if (\is_array($value)) {
            foreach ($value as &$row) {
                if ($row instanceof PremappingStruct) {
                    $row = $row->jsonSerialize();
                }

                unset($row['extensions']);
                if (isset($row['mapping']) && \is_array($row['mapping'])) {
                    foreach ($row['mapping'] as &$mapping) {
                        if ($mapping instanceof PremappingEntityStruct) {
                            $mapping = $mapping->jsonSerialize();
                        }

                        unset($mapping['extensions']);
                    }
                }

                if (isset($row['choices']) && \is_array($row['choices'])) {
                    foreach ($row['choices'] as &$choice) {
                        if ($choice instanceof PremappingChoiceStruct) {
                            $choice = $choice->jsonSerialize();
                        }

                        unset($choice['extensions']);
                    }
                }
            }

            $data->setValue($value);

            if ($field->is(Required::class)) {
                $this->validate([new NotBlank()], $data, $parameters->getPath());
            }

            $constraints = $this->getConstraints($field);
            $path = $parameters->getPath() . '/' . $field->getPropertyName();

            $this->validate($constraints, $data, $path);

            $value = Json::encode($value);
        }

        yield $field->getStorageName() => $value;
    }

    /**
     * @return list<PremappingStruct>|null
     */
    public function decode(Field $field, mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $raw = parent::decode($field, $value);

        $array = [];
        foreach ($raw as $premapping) {
            $mappings = [];
            foreach ($premapping['mapping'] as $mapping) {
                $mappings[] = new PremappingEntityStruct(
                    $mapping['sourceId'],
                    $mapping['description'],
                    $mapping['destinationUuid'],
                );
            }

            $choices = [];
            foreach ($premapping['choices'] as $choice) {
                $choices[] = new PremappingChoiceStruct(
                    $choice['uuid'],
                    $choice['description'],
                );
            }

            $array[] = new PremappingStruct(
                $premapping['entity'],
                $mappings,
                $choices,
            );
        }

        return $array;
    }

    protected function getConstraints(Field $field): array
    {
        return [
            new All([new Collection([
                'entity' => [new NotBlank(), new Type('string')],
                'mapping' => [
                    new Type('array'),
                    new All([new Collection([
                        'allowExtraFields' => true,
                        'fields' => [
                            'sourceId' => [new NotBlank(), new Type('string')],
                            'description' => [new NotBlank(), new Type('string')],
                            'destinationUuid' => [new NotBlank(), new Type('string')],
                        ],
                    ])]),
                ],
                'choices' => [
                    new Type('array'),
                    new All([new Collection([
                        'allowExtraFields' => true,
                        'fields' => [
                            'uuid' => [new NotBlank(), new Type('string')],
                            'description' => [new NotBlank(), new Type('string')],
                        ],
                    ])]),
                ],
            ])]),
        ];
    }
}
