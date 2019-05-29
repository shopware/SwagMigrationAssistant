<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Exception\Shopware55LocalReaderNotFoundException;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\LocalReaderInterface;

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
     * @throws Shopware55LocalReaderNotFoundException
     */
    public function getReader(MigrationContextInterface $migrationContext): LocalReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports($migrationContext->getProfileName(), $migrationContext->getDataSet())) {
                return $reader;
            }
        }

        throw new Shopware55LocalReaderNotFoundException($migrationContext->getDataSet()::getEntity());
    }
}
