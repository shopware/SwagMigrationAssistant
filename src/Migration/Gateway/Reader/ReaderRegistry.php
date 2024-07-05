<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class ReaderRegistry implements ReaderRegistryInterface
{
    /**
     * @param ReaderInterface[] $readers
     */
    public function __construct(private readonly iterable $readers)
    {
    }

    /**
     * @throws MigrationException
     */
    public function getReader(MigrationContextInterface $migrationContext): ReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports($migrationContext)) {
                return $reader;
            }
        }

        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            throw MigrationException::migrationContextPropertyMissing('DataSet');
        }

        throw MigrationException::readerNotFound($dataSet::getEntity());
    }

    /**
     * @return ReaderInterface[]
     */
    public function getReaderForTotal(MigrationContextInterface $migrationContext): array
    {
        $readers = [];
        foreach ($this->readers as $reader) {
            if ($reader->supportsTotal($migrationContext)) {
                $readers[] = $reader;
            }
        }

        return $readers;
    }
}
