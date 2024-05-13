import {test as base, expect, test} from '@acceptance/fixtures/AcceptanceTest';

// ToDo MIG-985: remove this workaround when the underlaying issue is fixed
export const MediaProcessObserver = base.extend({
    mediaProcessObserver: async ({adminApiContext}, use) => {
        const isMediaProcessing = async () => {
            const response = await adminApiContext.get(`/api/_action/migration/is-media-processing`, {});
            await expect(response.ok()).toBeTruthy();
            return await response.json();
        };

        await use({
            isMediaProcessing
        });
    },
});
