/**
 * @sw-package after-sales
 */
import type { Entity } from '@shopware-ag/admin-extension-sdk/es/data/_internals/Entity';

type TEntity<T> = Entity<T>;

type MigrationStep =
    | 'idle'
    | 'fetching'
    | 'writing'
    | 'media-processing'
    | 'cleanup'
    | 'indexing'
    | 'waiting-for-approve'
    | 'aborting'
    | 'finished'
    | 'aborted';

type MigrationState = {
    step: MigrationStep;
    progress: number;
    total: number;
};

type MigrationProfile = {
    name: string;
    sourceSystemName: string;
    version: string;
    author: string;
    icon?: string;
};

type MigrationGateway = {
    name: string;
    snippet: string;
};

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

type PremappingEntity = {
    sourceId: string;
    description: string;
    destinationUuid: string;
};

type PremappingChoice = {
    uuid: string;
    description: string;
};

type Premapping = {
    entity: string;
    choices: PremappingChoice[];
    mapping: PremappingEntity[];
};

type CredentialFields = {
    [key: string]: {
        endpoint: string;
    };
};

/**
 * @private
 */
export {
    TEntity,
    MigrationStep,
    MigrationState,
    MigrationProfile,
    MigrationGateway,
    DataSelection,
    Premapping,
    PremappingEntity,
    PremappingChoice,
    CredentialFields,
    EnvironmentInformation,
};
