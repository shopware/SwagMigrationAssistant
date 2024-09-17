<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

/**
 * ToDo: One lookup service for each "lookup" functionality
 *
 * Responsible for looking up existing Uuids / things in SW6 (target system)
 * to associate / reference them during a migration.
 *
 */
#[Package('services-settings')]
class LookupService implements ResetInterface
{
    /** @var array<string, string> */
    protected array $lookupCache = [];

    /**
     * @param EntityRepository $repo
     * @internal
     */
    public function __construct(
        protected readonly EntityRepository $repo
    )
    {
    }

    // ToDo: add lookup method (hint: see old MappingService)

    public function reset()
    {
        $this->lookupCache = [];
    }
}
