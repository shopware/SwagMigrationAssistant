/**
 * @sw-package fundamentals@after-sales
 * @private
 */
import type Criteria from '@shopware-ag/meteor-admin-sdk/es/data/Criteria';
import type { ApiContext } from '@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection';
import type { Entity as MeteorEntity } from '@shopware-ag/meteor-admin-sdk/es/_internals/data/Entity';
import type MeteorEntityCollection from '@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection';
import type { Repository as MeteorRepository } from '@shopware-ag/meteor-admin-sdk/es/data/repository';

export { MIGRATION_LOG_LEVEL } from '../module/swag-migration/component/swag-migration-error-resolution/swag-migration-error-resolution-step';

export type TEntity<EntityName extends keyof EntitySchema.Entities> = MeteorEntity<EntityName>;

export type TEntityCollection<EntityName extends keyof EntitySchema.Entities> = MeteorEntityCollection<EntityName>;

export type TRepository<EntityName extends keyof EntitySchema.Entities> = MeteorRepository<EntityName> & {
    search(criteria: Criteria, context?: ApiContext): Promise<TEntityCollection<EntityName>>;
    create(context?: ApiContext, entityId?: string): TEntity<EntityName>;
};

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

type MigrationCredentials = Record<string, string | number | boolean | null>;

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
