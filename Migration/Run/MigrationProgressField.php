<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\JsonFieldAccessorBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

class MigrationProgressField extends JsonField
{
    public function __construct(
        string $storageName,
        string $propertyName
    ) {
        parent::__construct($storageName, $propertyName, [
            (new StringField('step', 'step'))->addFlags(new Required()),
            (new IntField('progress', 'progress'))->addFlags(new Required()),
            (new IntField('total', 'total'))->addFlags(new Required()),
            (new StringField('currentEntity', 'currentEntity'))->addFlags(new Required()),
            (new IntField('currentProgress', 'currentProgress'))->addFlags(new Required()),
            (new ListField('dataSets', 'dataSets', StringField::class))->addFlags(new Required())
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