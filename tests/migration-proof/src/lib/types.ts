export interface EntityCountExpectation {
    label: string;
    sourceQuery: string;
    targetEntity: string;
    expectedTotal: number;
}

export interface MigrationState {
    step: string;
    progress: number;
    total: number;
}

export interface DataSelection {
    id: string;
    requiredSelection: boolean;
}

export interface PremappingChoice {
    uuid: string;
    description: string;
}

export interface PremappingEntry {
    sourceId: string;
    description: string;
    destinationUuid: string;
}

export interface PremappingGroup {
    entity: string;
    mapping: PremappingEntry[];
    choices: PremappingChoice[];
}

export interface RunEntity {
    id: string;
    step: string;
    createdAt?: string;
}

export interface GroupedLog {
    code: string;
    count: number;
    entity: string | null;
    level: string;
}

export interface GroupedLogResponse {
    total: number;
    downloadUrl: string;
    items: GroupedLog[];
}

export interface LogLevelCounts {
    error: number;
    warning: number;
    info: number;
}

export interface LogGroupsResponse {
    total: number;
    items: Record<string, unknown>[];
    levelCounts: LogLevelCounts;
}

export interface Fixture {
    connectionName: string;
    profileName: string;
    gatewayName: string;
    dataSelectionIds: string[];
    countExpectations: EntityCountExpectation[];
}
