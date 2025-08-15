/**
 * @sw-package after-sales
 */
import type { Entity } from '@shopware-ag/admin-extension-sdk/es/data/_internals/Entity';
import type Repository from 'shopware-administration/src/core/data/repository.data';

type TEntity<T> = Entity<T>;

type TRepository<T> = Repository<T>;

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

type MigrationEnvironmentInformation = {
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

/**
 * @private
 */
export {
    TEntity,
    TRepository,
    MigrationStep,
    MigrationState,
    MigrationProfile,
    MigrationGateway,
    MigrationDataSelection,
    MigrationPremapping,
    MigrationPremappingEntity,
    MigrationPremappingChoice,
    MigrationEnvironmentInformation,
    MigrationCredentials,
};
