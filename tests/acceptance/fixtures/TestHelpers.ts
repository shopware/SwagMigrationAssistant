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

export const screenshotScopeSelectors = {
    modal: '.sw-modal[class*="swag-migration"] .sw-modal__dialog',
    page: '.swag-migration-index .sw-card-view__content',
    smartBar: '.swag-migration-index .sw-page__smart-bar',
} as const;

export const dynamicElementSelectors = [
    '.swag-migration-progress-bar__caption',
    '.mt-progress-bar__track',
    '[class*="timestamp"]',
    '[class*="date"]',
    ...loaderSelectors,
] as const;

export function getMask(page: Page, additional: string[] = []): Locator[] {
    return [
        ...dynamicElementSelectors,
        ...additional,
    ].map((selector) => page.locator(selector));
}

export async function getScreenshotScope(page: Page): Promise<Locator> {
    const modal = page.locator(screenshotScopeSelectors.modal).last();

    if (await modal.isVisible()) {
        return modal;
    }

    return page.locator(screenshotScopeSelectors.page);
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
