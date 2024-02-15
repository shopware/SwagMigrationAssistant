<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\ConverterNotFoundException;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class ConverterRegistry implements ConverterRegistryInterface
{
    /**
     * @param ConverterInterface[] $converters
     */
    public function __construct(private readonly iterable $converters)
    {
    }

    /**
     * @throws ConverterNotFoundException
     */
    public function getConverter(MigrationContextInterface $migrationContext): ConverterInterface
    {
        foreach ($this->converters as $converter) {
            if ($converter->supports($migrationContext)) {
                return $converter;
            }
        }

        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            throw MigrationException::migrationContextPropertyMissing('Connection');
        }

        throw MigrationException::converterNotFound($connection->getProfileName());
    }
}
