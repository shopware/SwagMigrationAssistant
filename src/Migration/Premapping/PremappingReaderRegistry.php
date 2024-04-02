<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Premapping;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class PremappingReaderRegistry implements PremappingReaderRegistryInterface
{
    /**
     * @param PremappingReaderInterface[] $preMappingReaders
     */
    public function __construct(private readonly iterable $preMappingReaders)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getPremappingReaders(MigrationContextInterface $migrationContext, array $dataSelectionIds): array
    {
        $preMapping = [];
        foreach ($this->preMappingReaders as $preMappingReader) {
            if ($preMappingReader->supports($migrationContext, $dataSelectionIds)) {
                $preMapping[] = $preMappingReader;
            }
        }

        return $preMapping;
    }
}
