<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\MailTemplate\Aggregate\MailHeaderFooter\MailHeaderFooterCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('fundamentals@after-sales')]
class MailHeaderFooterProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<MailHeaderFooterCollection> $mailHeaderFooterRepo
     */
    public function __construct(private readonly EntityRepository $mailHeaderFooterRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::MAIL_HEADER_FOOTER;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->mailHeaderFooterRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, [
            'mailHeaderFooterId',
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->mailHeaderFooterRepo, $context);
    }
}
