<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class SystemConfigProvider extends AbstractProvider
{
    /**
     * @var string[]
     */
    public static array $CONFIG_KEY_BLOCK_LIST = [
        // shop instance, license related or consent data
        'core.app.shopId',
        'core.basicInformation.shopName',
        'core.basicInformation.activeCaptchas',
        'core.basicInformation.activeCaptchasV2',
        'core.store.licenseHost',
        'core.store.licenseKey',
        'core.store.verificationHash',
        'core.store.shopSecret',
        'core.store.apiUri',
        'core.store.hubspotApiUri',
        'core.usageData.consentState',
        'core.update.channel',
        'core.update.code',
        'core.update.apiUri',
        'core.basicInformation.email',
        'core.newsletter.subscribeDomain',
        'core.scheduled_indexers',
        // cloud unique, read only or disabled to modify
        'swag.saas.netPromoterScoreFirstOrderPrompted',
        'swag.saas.netPromoterScoreGoneLivePrompted',
        'swag.saas.netPromoterScoreInputDate',
        'core.sitemap.sitemapRefreshStrategy',
        'core.sitemap.sitemapRefreshTime',
        'swag.saas.onboardingInfo',
        'core.logging.entryLimit',
        'core.logging.entryLifetimeSeconds',
        'core.logging.cleanupInterval',
        'swag.saas.shopstatus',
        'swag.commercial.lastTurnoverReportDate',
        'core.mailerSettings.emailAgent',
        'core.mailerSettings.sendMailOptions',
        'core.mailerSettings.host',
        'core.mailerSettings.port',
        'core.mailerSettings.username',
        'core.mailerSettings.password',
        'core.mailerSettings.encryption',
        'core.mailerSettings.senderAddress',
        'core.mailerSettings.deliveryAddress',
        'core.mailerSettings.disableDelivery',
        // paypal config
        'SwagPayPal.settings.clientId',
        'SwagPayPal.settings.clientSecret',
        'SwagPayPal.settings.clientIdSandbox',
        'SwagPayPal.settings.clientSecretSandbox',
        'SwagPayPal.settings.sandbox',
        'SwagPayPal.settings.webhookId',
        'SwagPayPal.settings.webhookExecuteToken',
        'SwagPayPal.settings.merchantLocation',
        'SwagPayPal.settings.plusCheckoutEnabled',
        'SwagPayPal.settings.loggingLevel',
        // id references or data that is calculated
        'core.cms.default_product_cms_page',
        'core.cms.default_category_cms_page',
        'core.tax.defaultTaxRate',
        'core.basicInformation.contactPage',
        'core.basicInformation.shippingPaymentInfoPage',
        'core.basicInformation.privacyPage',
        'core.basicInformation.imprintPage',
        'core.basicInformation.revocationPage',
        'core.basicInformation.tosPage',
        'core.listing.defaultSorting',
        'storefront.themeSeed',
    ];

    /**
     * @param EntityRepository<SystemConfigCollection> $systemConfigRepo
     */
    public function __construct(private readonly EntityRepository $systemConfigRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::SYSTEM_CONFIG;
    }

    /**
     * @return array<mixed>
     */
    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsAnyFilter('configurationKey', self::$CONFIG_KEY_BLOCK_LIST),
        ]));
        $criteria->addSorting(new FieldSorting('salesChannelId', FieldSorting::DESCENDING), new FieldSorting('id'));
        $result = $this->systemConfigRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();

        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsAnyFilter('configurationKey', self::$CONFIG_KEY_BLOCK_LIST),
        ]));

        return $this->readTotalFromRepo($this->systemConfigRepo, $context, $criteria);
    }
}
