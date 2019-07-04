<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Profile\ReaderInterface;

interface LocalReaderInterface extends ReaderInterface
{
    public function supports(string $profileName, DataSet $dataSet): bool;
}
