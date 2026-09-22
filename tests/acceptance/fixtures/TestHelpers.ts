import type { Page, Locator } from 'playwright-core';
import { expect } from '@shopware-ag/acceptance-test-suite';

export const LOADING_TIMEOUT = 30_000; // 30s

export const VIEWPORT = {
    DEFAULT: { width: 1440, height: 1080 },
};

export const loaderSelectors = [
    '.sw-loader-element',
    '.mt-loader__element',
    '.mt-skeleton-bar',
    '.sw-skeleton',
] as const;

export const dynamicElementSelectors = [
    '.sw-version__info',
    '.sw-avatar',
    '.sw-admin-menu__user-name',
    '.swag-migration-progress-bar__caption',
    '.mt-progress-bar__track',
    '[class*="timestamp"]',
    '[class*="date"]',
    ...loaderSelectors,
] as const;

export const defaultIgnoredSelectors = [
    '.sw-page__search-bar',
    '.sw-page__top-bar-actions',
] as const;

export function getMask(page: Page, additional: string[] = []): Locator[] {
    return [
        ...defaultIgnoredSelectors,
        ...dynamicElementSelectors,
        ...additional,
    ].map((selector) => page.locator(selector));
}

export async function expectSnapshot(
    page: Page,
    name: string,
    options: { mask?: Locator[]; target?: Locator; clip?: boolean } = {},
): Promise<void> {
    let target = options.target;

    if (!target) {
        const modalDialog = page.locator('.sw-modal__dialog').last();
        const modalIsOpen = (await modalDialog.count()) > 0 && (await modalDialog.isVisible());

        target = modalIsOpen ? modalDialog : page.locator('.sw-page');
    }

    const mask = options.mask ?? getMask(page);

    // A target that never settles fails element stability check.
    // Skips per element wait while still framing only the target.
    if (options.clip) {
        await target.waitFor({ state: 'visible' });
        const clip = await target.boundingBox();

        await expect.soft(page).toHaveScreenshot(
            name,
            clip ? { mask, clip } : { mask }
        );

        return;
    }

    await expect.soft(target).toHaveScreenshot(name, { mask });
}

export async function waitForLoaders(page: Page, timeout = LOADING_TIMEOUT): Promise<void> {
    const loader = page.locator(loaderSelectors.join(', '));

    // wait for loader to appear
    await loader
        .first()
        .waitFor({ state: 'visible', timeout: 1000 })
        .catch(() => {
            // loader may not appear, ignore timeout
        });

    await expect(loader).toHaveCount(0, { timeout });
}
