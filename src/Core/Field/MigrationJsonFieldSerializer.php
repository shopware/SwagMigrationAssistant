<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Field;

use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\AbstractFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Json;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @internal
 */
#[Package('after-sales')]
class MigrationJsonFieldSerializer extends AbstractFieldSerializer
{
    public function encode(Field $field, EntityExistence $existence, KeyValuePair $data, WriteParameterBag $parameters): \Generator
    {
        if (!$field instanceof MigrationJsonField) {
            throw DataAbstractionLayerException::invalidSerializerField(MigrationJsonField::class, $field);
        }

        $this->validateIfNeeded($field, $existence, $data, $parameters);

        $value = $data->getValue();

        $value = Json::encode($value);

        yield $field->getStorageName() => $value;
    }

    public function decode(Field $field, mixed $value): mixed
    {
        if (!$field instanceof MigrationJsonField) {
            throw DataAbstractionLayerException::invalidSerializerField(MigrationJsonField::class, $field);
        }

        return json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
    }

    protected function getConstraints(Field $field): array
    {
        $constraints = [];
        if ($field->is(Required::class)) {
            $constraints[] = new NotNull();
        }

        return $constraints;
    }
}
