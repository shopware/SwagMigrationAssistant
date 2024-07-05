<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware54\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;

#[Package('services-settings')]
class Shopware54OrderDocumentConverter extends OrderDocumentConverter
{
    private const PREFIX = 'migration_';

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware54Profile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === OrderDocumentDataSet::getEntity();
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['ID'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $data['id'] = $data['ID'];

        unset($data['ID']);

        return parent::convert($data, $context, $migrationContext);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function getDocumentType(array $data): array
    {
        $data['key'] = match ($data['id']) {
            '1' => 'invoice',
            '2' => 'delivery_note',
            '3' => 'credit_note',
            '4' => 'storno',
            default => $this->createName($data['name'])
        };

        return parent::getDocumentType($data);
    }

    private function createName(string $name): string
    {
        return self::PREFIX . \mb_strtolower((string) \preg_replace('/\s+/', '_', $name));
    }
}
