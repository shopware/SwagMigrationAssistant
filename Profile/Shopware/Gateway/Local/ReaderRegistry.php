<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Exception\LocalReaderNotFoundException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\LocalReaderInterface;

class ReaderRegistry
{
    /**
     * @var LocalReaderInterface[]
     */
    private $readers;

    public function __construct(iterable $readers)
    {
        $this->readers = $readers;
    }

    /**
     * @throws LocalReaderNotFoundException
     */
    public function getReader(MigrationContextInterface $migrationContext): LocalReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports($migrationContext->getConnection()->getProfileName(), $migrationContext->getDataSet())) {
                return $reader;
            }
        }

        throw new LocalReaderNotFoundException($migrationContext->getDataSet()::getEntity());
    }
}
