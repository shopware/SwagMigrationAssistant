/**
 * @sw-package fundamentals@after-sales
 * @private
 */
import type { AxiosInstance, AxiosResponse } from 'axios';
import type { PropType as VuePropType } from 'vue';
import type Criteria from '@shopware-ag/meteor-admin-sdk/es/data/Criteria';
import type { ApiContext as MeteorApiContext } from '@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection';
import type { Entity as MeteorEntity } from '@shopware-ag/meteor-admin-sdk/es/_internals/data/Entity';
import type MeteorEntityCollection from '@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection';
import type EntityDefinition from '@administration/src/core/data/entity-definition.data';
import type { ApiResponse, ApiServiceBase } from './api-service.types';

type AppContext = {
    config: {
        version?: string;
        settings?: Record<string, unknown>;
    };
};

type ShopwareErrorConstructor = new (error: ShopwareError) => ShopwareError;

type ShopwareApiServiceConstructor = {
    new (httpClient: AxiosInstance, loginService: unknown, apiEndpoint: string, contentType?: string): ApiServiceBase;
    handleResponse<T = unknown>(response: AxiosResponse<T>): ApiResponse<T>;
};

type ShopwareModuleInfo = {
    routes: Map<string, { routeKey?: string; name?: string }>;
};

type NotificationStore = {
    notifications: Record<string, unknown>;
    createNotification(notification: { variant?: string; title?: string; message?: string; [key: string]: unknown }): void;
    $reset(): void;
};

type SessionStore = {
    currentLocale?: string;
    currentUser?: {
        username?: string;
        [key: string]: unknown;
    } | null;
};

type ShopwareStoreConfig<TState, TGetters, TActions> = {
    id: string;
    state: () => TState;
    getters?: TGetters;
    actions?: TActions;
};

type ShopwareGetterResults<TGetters> = {
    [K in keyof TGetters]: TGetters[K] extends (...args: never[]) => infer TResult ? TResult : never;
};

type ShopwareStoreInstance<TState, TGetters, TActions> = TState & ShopwareGetterResults<TGetters> & TActions;

type ShopwareBuiltinStores = {
    notification: NotificationStore;
    session: SessionStore;
};

type ShopwareStoreRegistry = PiniaRootState & ShopwareBuiltinStores;

declare global {
    type PropType<T> = VuePropType<T>;

    type Entity<EntityName extends keyof EntitySchema.Entities> = MeteorEntity<EntityName>;
    type EntityCollection<EntityName extends keyof EntitySchema.Entities> = MeteorEntityCollection<EntityName>;

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
        acl: {
            can(privilege: string): boolean;
        };
        loginService: unknown;
        privileges: {
            addPrivilegeMappingEntry(entry: unknown): void;
        };
    }

    const Shopware: {
        Classes: {
            ApiService: ShopwareApiServiceConstructor;
            ShopwareError: ShopwareErrorConstructor;
        };
        Component: {
            wrapComponentConfig<T>(config: T): T;
            register(name: string, component: unknown): void;
            extend(name: string, baseComponent: string, component: unknown): void;
            override(name: string, component: unknown): void;
            build(name: string): Promise<unknown>;
            getComponentHelper(): {
                mapState(...args: unknown[]): Record<string, (...args: never[]) => unknown>;
            };
            getComponentRegistry(): {
                has(name: string): boolean;
            };
        };
        Context: {
            api: MeteorApiContext;
            app: AppContext;
        };
        Data: {
            Criteria: typeof Criteria;
        };
        EntityDefinition: {
            has(entityName: string): boolean;
            get(entityName: string): EntityDefinition<never>;
        };
        Filter: {
            getByName(name: string): (...args: unknown[]) => unknown;
        };
        Locale: {
            extend(locale: string, snippets: Record<string, unknown>): void;
        };
        Mixin: {
            register<T>(name: string, mixin: T): T;
            getByName(name: string): unknown;
        };
        Module: {
            register(name: string, module: unknown): void;
            getModuleByEntityName(entityName: string): ShopwareModuleInfo | null;
        };
        Application: {
            addServiceProvider(name: string, provider: (container: ServiceContainer) => unknown): void;
            getContainer(name: 'init'): {
                httpClient: AxiosInstance;
            };
        };
        Service: {
            <K extends keyof ServiceContainer>(serviceName: K): ServiceContainer[K];
        };
        Snippet: {
            t(key: string): string;
            tc(key: string): string;
            te(key: string): boolean;
        };
        Store: {
            register<TState extends object, TGetters extends object, TActions extends object>(
                store: ShopwareStoreConfig<TState, TGetters, TActions>,
            ): () => ShopwareStoreInstance<TState, TGetters, TActions>;
            get<K extends keyof ShopwareStoreRegistry>(storeName: K): ShopwareStoreRegistry[K];
        };
        Utils: {
            debug: {
                warn(...args: unknown[]): void;
            };
            debounce<T extends (...args: never[]) => unknown>(fn: T, wait?: number): T;
            format: {
                date(value: Date | string | null | undefined): string;
            };
            object: {
                get(object: unknown, path: string): unknown;
                set(object: Record<string, unknown>, path: string, value: unknown): void;
            };
        };
    };
}
