<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

#[Package('services-settings')]
class MigrationProgressFieldSerializer extends JsonFieldSerializer
{
    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        $value = $data->getValue();
        if ($value instanceof MigrationProgress) {
            $value = $value->jsonSerialize();
        }

        if (\is_array($value)) {
            unset($value['extensions']);

            if (isset($value['dataSets']) && $value['dataSets'] instanceof ProgressDataSetCollection) {
                $value['dataSets'] = $value['dataSets']->jsonSerialize();

                foreach ($value['dataSets'] as &$dataSet) {
                    if ($dataSet instanceof ProgressDataSet) {
                        $dataSet = $dataSet->jsonSerialize();
                    }
                }
            }

            if (isset($value['dataSets']) && \is_array($value['dataSets'])) {
                foreach ($value['dataSets'] as &$dataSet) {
                    unset($dataSet['extensions']);
                }
            }

            $data = new KeyValuePair($data->getKey(), $value, $data->isRaw());
        }

        $constraints = $this->getConstraints($field);
        $path = $parameters->getPath() . '/' . $field->getPropertyName();

        $this->validate($constraints, $data, $path);

        yield from parent::encode($field, $existence, $data, $parameters);
    }

    public function decode(Field $field, mixed $value): ?MigrationProgress
    {
        if ($value === null) {
            return null;
        }

        $raw = \json_decode((string) $value, true, 512, \JSON_THROW_ON_ERROR);
        $progressDataSetCollection = new ProgressDataSetCollection();

        if (isset($raw['dataSets']) && \array_is_list($raw['dataSets'])) {
            $progressDataSetCollection->fromArray($raw['dataSets']);
        }

        return new MigrationProgress(
            (int) $raw['progress'],
            (int) $raw['total'],
            $progressDataSetCollection,
            (string) $raw['currentEntity'],
            (int) $raw['currentEntityProgress'],
            (int) $raw['exceptionCount'],
            (bool) $raw['isAborted'],
        );
    }

    protected function getConstraints(Field $field): array
    {
        return [
            new Collection([
                'progress' => [new NotBlank(), new Type('int')],
                'total' => [new NotBlank(), new Type('int')],
                'currentEntity' => [new NotBlank(), new Type('string')],
                'currentEntityProgress' => [new NotBlank(), new Type('int')],
                'exceptionCount' => [new NotBlank(), new Type('int')],
                'isAborted' => [new NotNull(), new Type('bool')],
                'dataSets' => [
                    new Type('array'),
                    new All([new Collection([
                        'allowExtraFields' => true,
                        'fields' => [
                            'entityName' => [new NotBlank(), new Type('string')],
                            'total' => [new NotBlank(), new Type('int')],
                        ],
                    ])]),
                ],
            ]),
        ];
    }
}
