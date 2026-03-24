<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Logging;

use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\MigrationConfiguration;

#[Package('fundamentals@after-sales')]
class DummyLoggingService extends LoggingService
{
    public function __construct()
    {
        /** @var StaticEntityRepository<SwagMigrationLoggingCollection> $repository */
        $repository = new StaticEntityRepository([], new SwagMigrationLoggingDefinition());

        parent::__construct(
            $repository,
            new NullLogger(),
            new MigrationConfiguration(),
        );
    }

    public function getLoggingArray(): array
    {
        return $this->buffer;
    }

    public function flush(): void
    {
    }

    public function reset(): void
    {
        $this->buffer = [];
    }
}
