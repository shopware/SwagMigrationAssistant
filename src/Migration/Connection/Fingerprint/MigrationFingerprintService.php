<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Fingerprint;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;

#[Package('fundamentals@after-sales')]
readonly class MigrationFingerprintService implements MigrationFingerprintServiceInterface
{
    /**
     * @internal
     *
     * @param EntityRepository<SwagMigrationConnectionCollection> $connectionRepo
     */
    public function __construct(
        private EntityRepository $connectionRepo,
    ) {
    }

    public function searchDuplicates(?string $fingerprint, Context $context, ?string $excludeConnectionId): bool
    {
        if (empty($fingerprint)) {
            return false;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sourceSystemFingerprint', $fingerprint));

        if (isset($excludeConnectionId)) {
            $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('id', $excludeConnectionId),
            ]));
        }

        return $this->connectionRepo->searchIds($criteria, $context)->getTotal() > 0;
    }
}
