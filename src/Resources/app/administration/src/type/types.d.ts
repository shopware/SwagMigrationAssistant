/**
 * @sw-package fundamentals@after-sales
 * @private
 */
import type Repository from '@administration/src/core/data/repository.data';

/**
 * @private
 */
export type { Entity as TEntity } from '@shopware-ag/admin-extension-sdk/es/data/_internals/Entity';
export type { default as TEntityCollection } from '@shopware-ag/admin-extension-sdk/es/data/_internals/EntityCollection';
export type { default as TCriteria } from '@shopware-ag/admin-extension-sdk/es/data/Criteria';

type TRepository<T> = Repository<T>;

type MigrationStep =
    | 'idle'
    | 'fetching'
    | 'error-resolution'
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
    profile: string;
    gateway: string;
    icon?: string;
};

type MigrationGateway = {
    name: string;
    snippet: string;
};

type MigrationEnvironmentInformation = {
    sourceSystemName?: string;
    migrationDisabled?: boolean;
    sourceSystemLocale?: string;
    sourceSystemDomain?: string;
    sourceSystemCurrency?: string;
    targetSystemLocale?: string;
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

type MigrationDataSelection = {
    id: string;
    dataSets: unknown[];
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

type MigrationPremappingEntity = {
    sourceId: string;
    description: string;
    destinationUuid: string | null;
};

type MigrationPremappingChoice = {
    uuid: string;
    description: string;
};

type MigrationPremapping = {
    entity: string;
    choices: MigrationPremappingChoice[];
    mapping: MigrationPremappingEntity[];
};

type MigrationCredentials = {
    endpoint: string;
    apiUser?: string;
    apiKey?: string;
    apiPassword?: string;
};

type MigrationConnection = {
    id: string;
    profile?: MigrationProfile;
    gateway?: MigrationGateway;
    credentialsFields?: MigrationCredentials;
};

type MigrationError = {
    code: string;
    count: number;
    entity: string;
    level: string;
};

type MigrationLog = {
    id: string;
    entityId?: string;
    entityName?: string;
    fieldName?: string;
    convertedData?: Record<string, unknown>;
    sourceData?: Record<string, unknown>;
};

type MigrationFix = {
    id: string;
    entityId: string;
    value: unknown;
};

/**
 * @private
 */
export {
    MIGRATION_LOG_LEVEL,
    TRepository,
    MigrationStep,
    MigrationState,
    MigrationProfile,
    MigrationGateway,
    MigrationError,
    MigrationConnection,
    MigrationDataSelection,
    MigrationPremapping,
    MigrationPremappingEntity,
    MigrationPremappingChoice,
    MigrationEnvironmentInformation,
    MigrationCredentials,
    MigrationLog,
    MigrationFix,
};
