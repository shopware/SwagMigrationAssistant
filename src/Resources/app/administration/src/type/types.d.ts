/**
 * @sw-package after-sales
 */
import type { Entity } from '@shopware-ag/admin-extension-sdk/es/data/_internals/Entity';

type TEntity<T> = Entity<T>;

type EnvironmentInformation = {
    sourceSystemName?: string;
    migrationDisabled?: boolean;
    sourceSystemLocale?: string;
    sourceSystemDomain?: string;
    sourceSystemCurrency?: string;
    displayWarnings?: {
        snippetKey: string;
        snippetArguments: string[];
        pluralizationCount: number;
    }[];
    requestStatus?: {
        code: string;
        isWarning: boolean;
    };
};

type DataSelection = {
    id: string;
    dataSets: [];
    total: number;
    snippet: string;
    position: number;
    dataType: string;
    entityNames: string[];
    entityTotals: number[];
    processMediaFiles: boolean;
    requiredSelection: boolean;
    dataSetsRequiredForCount: string[];
};

type PremappingGroup = {
    entity: string;
    mapping: {
        destinationUuid: string;
        sourceId: string;
    }[];
    choices: unknown;
};

/**
 * @private
 */
export { TEntity, DataSelection, PremappingGroup, EnvironmentInformation };
