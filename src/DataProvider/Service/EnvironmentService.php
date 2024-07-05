<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Service;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\AbstractExtensionDataProvider;
use Shopware\Core\Framework\Store\Services\StoreClient;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Language\LanguageCollection;

#[Package('services-settings')]
class EnvironmentService implements EnvironmentServiceInterface
{
    /**
     * @param EntityRepository<CurrencyCollection> $currencyRepository
     * @param EntityRepository<LanguageCollection> $languageRepository
     */
    public function __construct(
        private readonly EntityRepository $currencyRepository,
        private readonly EntityRepository $languageRepository,
        private readonly string $shopwareVersion,
        private readonly string $shopwareRevision,
        private readonly StoreClient $storeClient,
        private readonly AbstractExtensionDataProvider $extensionDataProvider,
    ) {
    }

    /**
     * @return array<string, string|bool|array<mixed>>
     */
    public function getEnvironmentData(Context $context): array
    {
        $defaultCurrency = $this->currencyRepository->search(new Criteria([Defaults::CURRENCY]), $context)->getEntities()->first();

        if ($defaultCurrency === null) {
            return [];
        }

        $defaultCurrencyIsoCode = $defaultCurrency->getIsoCode();

        $languageCriteria = new Criteria([Defaults::LANGUAGE_SYSTEM]);
        $languageCriteria->addAssociation('locale');
        $defaultLanguage = $this->languageRepository->search($languageCriteria, $context)->getEntities()->first();

        if ($defaultLanguage === null) {
            return [];
        }

        $defaultLanguageLocale = $defaultLanguage->getLocale();
        $defaultLanguageLocaleCode = '';
        if ($defaultLanguageLocale !== null) {
            $defaultLanguageLocaleCode = $defaultLanguageLocale->getCode();
        }
        $updateAvailable = $this->isPluginUpdateAvailable($context);

        return [
            'defaultShopLanguage' => $defaultLanguageLocaleCode,
            'defaultCurrency' => $defaultCurrencyIsoCode,
            'shopwareVersion' => $this->shopwareVersion,
            'versionText' => $this->shopwareVersion,
            'revision' => $this->shopwareRevision,
            'additionalData' => [],
            'updateAvailable' => $updateAvailable,
        ];
    }

    private function isPluginUpdateAvailable(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'SwagMigrationAssistant'));
        $extensions = $this->extensionDataProvider->getInstalledExtensions($context, false, $criteria);

        try {
            $systemContext = new Context(
                new SystemSource(),
                $context->getRuleIds(),
                $context->getCurrencyId(),
                $context->getLanguageIdChain(),
                $context->getVersionId(),
                $context->getCurrencyFactor(),
                $context->considerInheritance(),
                $context->getTaxState(),
                $context->getRounding()
            );

            $updatesList = $this->storeClient->getExtensionUpdateList($extensions, $systemContext);
        } catch (\Exception) {
            // ignore failures here => so it is unknown if an update is available,
            // but it is still possible to connect to this shop
            return false;
        }

        return \count($updatesList) === 1;
    }
}
