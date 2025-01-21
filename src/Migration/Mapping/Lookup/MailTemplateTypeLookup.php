<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class MailTemplateTypeLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<MailTemplateTypeCollection> $mailTemplateTypeRepository
     *
     * @internal
     */
    public function __construct(private readonly EntityRepository $mailTemplateTypeRepository)
    {
    }

    public function get(string $technicalName, Context $context): ?string
    {
        if (\array_key_exists($technicalName, $this->cache)) {
            return $this->cache[$technicalName];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));

        $mailTemplateTypeId = $this->mailTemplateTypeRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$technicalName] = $mailTemplateTypeId;

        return $mailTemplateTypeId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
