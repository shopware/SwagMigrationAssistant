<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Service;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageEntity;

#[Package('services-settings')]
class EnvironmentService implements EnvironmentServiceInterface
{
    public function __construct(
        private readonly EntityRepository $currencyRepository,
        private readonly EntityRepository $languageRepository,
        private readonly string $shopwareVersion,
        private readonly string $shopwareRevision
    ) {
    }

    public function getEnvironmentData(Context $context): array
    {
        /** @var CurrencyEntity $defaultCurrency */
        $defaultCurrency = $this->currencyRepository->search(new Criteria([Defaults::CURRENCY]), $context)->first();
        $defaultCurrencyIsoCode = $defaultCurrency->getIsoCode();

        $languageCriteria = new Criteria([Defaults::LANGUAGE_SYSTEM]);
        $languageCriteria->addAssociation('locale');
        /** @var LanguageEntity $defaultLanguage */
        $defaultLanguage = $this->languageRepository->search($languageCriteria, $context)->first();
        $defaultLanguageLocale = $defaultLanguage->getLocale();
        $defaultLanguageLocaleCode = '';
        if ($defaultLanguageLocale !== null) {
            $defaultLanguageLocaleCode = $defaultLanguageLocale->getCode();
        }

        return [
            'defaultShopLanguage' => $defaultLanguageLocaleCode,
            'defaultCurrency' => $defaultCurrencyIsoCode,
            'shopwareVersion' => $this->shopwareVersion,
            'versionText' => $this->shopwareVersion,
            'revision' => $this->shopwareRevision,
            'additionalData' => [],
            'updateAvailable' => false,
        ];
    }
}
