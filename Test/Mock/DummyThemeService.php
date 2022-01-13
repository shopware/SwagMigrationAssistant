<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Storefront\Theme\ThemeService;

class DummyThemeService extends ThemeService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $themeSalesChannelRepository;

    public function __construct(EntityRepositoryInterface $themeSalesChannelRepository)
    {
        $this->themeSalesChannelRepository = $themeSalesChannelRepository;
    }

    public function assignTheme(string $themeId, string $salesChannelId, Context $context, bool $skipCompile = false): bool
    {
        $this->themeSalesChannelRepository->upsert([[
            'themeId' => $themeId,
            'salesChannelId' => $salesChannelId,
        ]], $context);

        return true;
    }
}
