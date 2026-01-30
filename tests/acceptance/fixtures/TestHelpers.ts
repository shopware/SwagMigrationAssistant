import type { Page, Locator } from 'playwright-core';
import { expect } from '@shopware-ag/acceptance-test-suite';

export const LOADING_TIMEOUT = 30_000; // 30s

export const VIEWPORT = {
    DEFAULT: { width: 1440, height: 1080 },
    LARGE: { width: 1440, height: 1440 },
};

export const loaderSelectors = [
    '.sw-loader-element',
    '.mt-loader-element',
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

export function getMask(page: Page, additional = []): Locator[] {
    return [
        ...dynamicElementSelectors,
        ...additional,
    ].map((selector) => page.locator(selector));
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

export async function withLargerViewport(
    page: Page,
    viewport: { width: number; height: number } = VIEWPORT.DEFAULT,
): Promise<() => Promise<void>> {
    const height = Math.max(viewport.height, VIEWPORT.LARGE.height);
    const width = Math.max(viewport.width, VIEWPORT.LARGE.width);

    const original = page.viewportSize();
    await page.setViewportSize({ width, height });

    return async () => {
        if (original) {
            await page.setViewportSize(original);
        }
    };
}
