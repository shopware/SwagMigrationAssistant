<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use SwagMigrationAssistant\Exception\MigrationException;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class LanguageLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @var array<string, LanguageEntity|null>
     */
    private array $defaultLanguageCache = [];

    /**
     * @param EntityRepository<LanguageCollection> $languageRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $languageRepository,
        private readonly LocaleLookup $localeLookup,
    ) {
    }

    /**
     * @throws MigrationException
     */
    public function get(string $localeCode, Context $context): ?string
    {
        if (\array_key_exists($localeCode, $this->cache)) {
            return $this->cache[$localeCode];
        }

        $localeUuid = $this->localeLookup->get($localeCode, $context);
        if ($localeUuid === null) {
            throw MigrationException::localeForLanguageLookupNotFound($localeCode);
        }

        $language = $this->getLanguage($localeUuid, $context);
        if (!$language instanceof LanguageEntity) {
            $this->cache[$localeCode] = null;

            return null;
        }

        $this->cache[$localeCode] = $language->getId();

        return $language->getId();
    }

    public function getLanguageEntity(Context $context): ?LanguageEntity
    {
        $languageUuid = $context->getLanguageId();

        if (isset($this->defaultLanguageCache[$languageUuid]) && $this->defaultLanguageCache[$languageUuid] instanceof LanguageEntity) {
            return $this->defaultLanguageCache[$languageUuid];
        }

        $criteria = new Criteria([$languageUuid]);
        $criteria->addAssociation('locale');

        $language = $this->languageRepository->search($criteria, $context)->first();

        if (!$language instanceof LanguageEntity) {
            $this->defaultLanguageCache[$languageUuid] = null;

            return null;
        }

        $this->defaultLanguageCache[$languageUuid] = $language;

        return $language;
    }

    public function reset(): void
    {
        $this->cache = [];
        $this->defaultLanguageCache = [];
    }

    private function getLanguage(string $localeUuid, Context $context): ?LanguageEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('localeId', $localeUuid));
        $criteria->setLimit(1);

        return $this->languageRepository->search($criteria, $context)->getEntities()->first();
    }
}
