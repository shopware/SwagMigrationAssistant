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
abstract class AbstractPremappingReader implements PremappingReaderInterface
{
    /**
     * @var array<string, PremappingEntityStruct>
     */
    protected array $connectionPremappingDictionary = [];

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
            if ($premapping->getEntity() === static::getMappingName()) {
                foreach ($premapping->getMapping() as $mapping) {
                    $this->connectionPremappingDictionary[$mapping->getSourceId()] = $mapping;
                }
            }
        }
    }
}
