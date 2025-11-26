<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Field;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StorageAware;
use Shopware\Core\Framework\Log\Package;

/**
 * This is an alternative for the JsonField and allows to save all simple data types
 * into a JSON database field, not just arrays.
 *
 * int, float, string, null and array
 *
 * @internal
 */
#[Package('fundamentals@after-sales')]
class AnyJsonField extends Field implements StorageAware
{
    public function __construct(
        private readonly string $storageName,
        string $propertyName,
    ) {
        parent::__construct($propertyName);
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    protected function getSerializerClass(): string
    {
        return AnyJsonFieldSerializer::class;
    }
}
