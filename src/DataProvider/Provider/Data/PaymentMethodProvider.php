<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class PaymentMethodProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepo
     */
    public function __construct(private readonly EntityRepository $paymentMethodRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PAYMENT_METHOD;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->paymentMethodRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->paymentMethodRepo, $context);
    }

    public function getProvidedTable(Context $context): array
    {
        return $this->readTableFromRepo($this->paymentMethodRepo, $context);
    }
}
