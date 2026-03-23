/**
 * @sw-package fundamentals@after-sales
 * @private
 */
import type { PropType as VuePropType } from 'vue';

/* eslint-disable @typescript-eslint/no-explicit-any */
declare global {
    type PropType<T> = VuePropType<T>;

    type Entity<EntityName extends keyof EntitySchema.Entities> = EntitySchema.Entities[EntityName];
    type EntityCollection<EntityName extends keyof EntitySchema.Entities> = Entity<EntityName>[] & Record<string, any>;

    interface ShopwareErrorMeta {
        parameters: {
            [key: string]: unknown;
        };
        [key: string]: unknown;
    }

    interface ShopwareError {
        code: string;
        meta?: ShopwareErrorMeta;
    }

    interface ServiceContainer {
        [key: string]: any;
    }

    const Shopware: {
        Classes: any;
        Component: any;
        Context: any;
        Data: any;
        EntityDefinition: any;
        Filter: any;
        Locale: any;
        Mixin: any;
        Module: any;
        Service: any;
        Snippet: any;
        Store: any;
        Utils: any;
        Application: any;
        [key: string]: any;
    };
}
/* eslint-enable @typescript-eslint/no-explicit-any */
