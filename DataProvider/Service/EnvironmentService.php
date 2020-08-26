<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Service;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageEntity;

class EnvironmentService implements EnvironmentServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepository;

    public function __construct(
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $languageRepository
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->languageRepository = $languageRepository;
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
            // ToDo replace dummy data with real environment information
            'shopwareVersion' => '6.3.0.0',
            'versionText' => 'v6.3.0.0 Stable version',
            'revision' => '1',
            'additionalData' => [],
            'updateAvailable' => false,
        ];
    }
}
