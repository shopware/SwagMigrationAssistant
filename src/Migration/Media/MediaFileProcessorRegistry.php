<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class MediaFileProcessorRegistry implements MediaFileProcessorRegistryInterface
{
    /**
     * @param MediaFileProcessorInterface[] $processors
     */
    public function __construct(private readonly iterable $processors)
    {
    }

    /**
     * @throws MigrationException
     */
    public function getProcessor(MigrationContextInterface $migrationContext): MediaFileProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($migrationContext)) {
                return $processor;
            }
        }

        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            throw MigrationException::entityNotExists(SwagMigrationConnectionEntity::class, $migrationContext->getRunUuid());
        }

        throw MigrationException::processorNotFound($connection->getProfileName(), $connection->getGatewayName());
    }
}
