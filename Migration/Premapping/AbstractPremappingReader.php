<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Premapping;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class AbstractPremappingReader implements PremappingReaderInterface
{
    /**
     * @var array
     */
    protected $connectionPremappingDictionary = [];

    protected function fillConnectionPremappingDictionary(MigrationContextInterface $migrationContext): void
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return;
        }

        $connectionMapping = $connection->getPremapping();
        if ($connectionMapping === null) {
            return;
        }

        foreach ($connectionMapping as $premapping) {
            if ($premapping['entity'] === static::getMappingName()) {
                foreach ($premapping['mapping'] as $mapping) {
                    $this->connectionPremappingDictionary[$mapping['sourceId']] = $mapping;
                }
            }
        }
    }
}
