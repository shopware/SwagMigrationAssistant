import {test as base, expect } from '@playwright/test';
import {FixtureTypes} from './AcceptanceTest';

export interface MediaProcessObserverStruct {
    isMediaProcessing: () => Promise<boolean>
}

// ToDo MIG-985: remove this workaround when the underlying issue is fixed
export const MediaProcessObserver = base.extend<FixtureTypes>({
    MediaProcessObserver: async ({ AdminApiContext }, use) => {
        const isMediaProcessing = async () => {
            const response = await AdminApiContext.get(`/api/_action/migration/is-media-processing`, {});
            expect(response.ok()).toBeTruthy();
            return await response.json();
        };

        await use({
            isMediaProcessing,
        });
    },
});
