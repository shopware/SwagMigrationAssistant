<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\JsonFieldAccessorBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class MigrationProgressField extends JsonField
{
    public function __construct(
        string $storageName,
        string $propertyName
    ) {
        parent::__construct($storageName, $propertyName, [
            (new IntField('progress', 'progress'))->addFlags(new Required()),
            (new IntField('total', 'total'))->addFlags(new Required()),
            (new StringField('currentEntity', 'currentEntity'))->addFlags(new Required()),
            (new IntField('currentEntityProgress', 'currentEntityProgress'))->addFlags(new Required()),
            (new ListField('dataSets', 'dataSets', JsonField::class))->addFlags(new Required()),
            (new IntField('exceptionCount', 'exceptionCount'))->addFlags(new Required()),
            (new BoolField('isAborted', 'isAborted'))->addFlags(new Required()),
        ]);
    }

    protected function getSerializerClass(): string
    {
        return MigrationProgressFieldSerializer::class;
    }

    protected function getAccessorBuilderClass(): ?string
    {
        return JsonFieldAccessorBuilder::class;
    }
}
