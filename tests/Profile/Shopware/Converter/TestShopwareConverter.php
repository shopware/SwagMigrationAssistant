<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\ShopwareConverter;

#[Package('fundamentals@after-sales')]
class TestShopwareConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return true;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ?ConvertStruct
    {
        return null;
    }

    public function setMigrationContext(MigrationContextInterface $migrationContext): void
    {
        $this->migrationContext = $migrationContext;
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function convertDateTimeValue(string $value, ?Context $context = null): array
    {
        $converted = [];
        $source = ['createdAt' => $value];

        $this->convertValue($converted, 'createdAt', $source, 'createdAt', self::TYPE_DATETIME, $context);

        return [$converted, $source];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function convertDateValue(string $value): array
    {
        $converted = [];
        $source = ['birthday' => $value];

        $this->convertValue($converted, 'birthday', $source, 'birthday', self::TYPE_DATE);

        return [$converted, $source];
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    public function convertAttributes(array $attributes, string $entityName, string $connectionName, ?Context $context = null): ?array
    {
        return $this->getAttributes($attributes, $entityName, $connectionName, [], $context);
    }
}
