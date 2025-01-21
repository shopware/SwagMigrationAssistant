<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\MailTemplate\MailTemplateCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class SystemDefaultMailTemplateLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<MailTemplateCollection> $mailTemplateRepository
     *
     * @internal
     */
    public function __construct(private readonly EntityRepository $mailTemplateRepository)
    {
    }

    public function get(string $typeId, Context $context): ?string
    {
        if (\array_key_exists($typeId, $this->cache)) {
            return $this->cache[$typeId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('systemDefault', true));
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $typeId));
        $criteria->setLimit(1);

        $mailTemplateId = $this->mailTemplateRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$typeId] = $mailTemplateId;

        return $mailTemplateId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
