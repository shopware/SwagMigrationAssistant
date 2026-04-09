import type { AxiosInstance, AxiosResponse } from 'axios';
import type {
    MigrationDataSelection,
    MigrationEnvironmentInformation,
    MigrationGateway,
    MigrationProfile,
    MigrationState,
    MigrationPremapping,
    MigrationCredentials,
    MigrationError,
} from '../../../type/types';
import type { ActionResponse, AdditionalHeaders, ApiResponse, ApiServiceBase } from '../../../type/api-service.types';

type MigrationLogLevel = keyof LogLevelCounts;

type GetLogGroupsFilter = {
    code?: string | null;
    status?: 'resolved' | 'unresolved' | null;
    entity?: string | null;
    field?: string | null;
};

type GetGroupedLogsOfRunResponse = {
    total: number;
    downloadUrl: string;
    items: MigrationError[];
};

type LogGroup = {
    code: string;
    entityName: string | null;
    fieldName: string | null;
    profileName: string;
    gatewayName: string;
    count: number;
    fixCount: number;
};

type LogLevelCounts = {
    error: number;
    warning: number;
    info: number;
};

type GetLogGroupsResponse = {
    total: number;
    items: LogGroup[];
    levelCounts: LogLevelCounts;
};

type GetUnresolvedLogsBatchInformationResponse = {
    count: number;
    limit: number;
};

type LogEntityIdsWithoutFixResponse = {
    entityIds: string[];
};

type ValidateResolutionResponse = {
    valid: boolean;
    violations: Array<{
        message: string;
        propertyPath?: string;
    }>;
};

type GetExampleFieldStructureResponse = {
    fieldType: string;
    example: string | null;
};

const ApiService = Shopware.Classes.ApiService;

/**
 * @private
 */
export const MIGRATION_API_SERVICE = 'migrationApiService';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export const MIGRATION_STEP = {
    IDLE: 'idle',
    FETCHING: 'fetching',
    ERROR_RESOLUTION: 'error-resolution',
    WRITING: 'writing',
    MEDIA_PROCESSING: 'media-processing',
    CLEANUP: 'cleanup',
    INDEXING: 'indexing',
    WAITING_FOR_APPROVE: 'waiting-for-approve',
    ABORTING: 'aborting',
} as const;

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default class MigrationApiService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: unknown, apiEndpoint = 'migration') {
        super(httpClient, loginService, apiEndpoint);
        this.apiService.name = MIGRATION_API_SERVICE;
    }

    private get apiService(): ApiServiceBase {
        return this as ApiServiceBase;
    }

    private getHeaders(additionalHeaders: AdditionalHeaders = {}): AdditionalHeaders {
        return this.apiService.getBasicHeaders(additionalHeaders);
    }

    private getActionPath(path: string): string {
        return `_action/${this.apiService.getApiBasePath()}/${path}`;
    }

    private handleResponse<T>(response: AxiosResponse<T>): ApiResponse<T> {
        return ApiService.handleResponse<T>(response) as ApiResponse<T>;
    }

    async createNewConnection(
        connectionId: string,
        connectionName: string,
        profileName: string,
        gatewayName: string,
        credentialFields: MigrationCredentials,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<MigrationEnvironmentInformation> {
        const response = await this.apiService.httpClient.post<MigrationEnvironmentInformation>(
            this.getActionPath('create-new-connection'),
            {
                connectionId,
                connectionName,
                profileName,
                gatewayName,
                credentialFields,
            },
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response) as MigrationEnvironmentInformation;
    }

    async updateConnectionCredentials(
        connectionId: string,
        credentialFields: MigrationCredentials,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('update-connection-credentials'),
            {
                connectionId,
                credentialFields,
            },
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response);
    }

    async checkConnection(
        connectionId: string,
        credentialFields?: MigrationCredentials,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<MigrationEnvironmentInformation> {
        const payload: { connectionId: string; credentialFields?: MigrationCredentials } = { connectionId };

        if (credentialFields) {
            payload.credentialFields = credentialFields;
        }

        const response = await this.apiService.httpClient.post<MigrationEnvironmentInformation>(
            this.getActionPath('check-connection'),
            payload,
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response) as MigrationEnvironmentInformation;
    }

    async getDataSelection(
        connectionId: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<MigrationDataSelection[]> {
        const response = await this.apiService.httpClient.get<MigrationDataSelection[]>(
            this.getActionPath('data-selection'),
            {
                headers: this.getHeaders(additionalHeaders),
                params: {
                    connectionId,
                },
            },
        );

        return this.handleResponse(response) as MigrationDataSelection[];
    }

    async generatePremapping(dataSelectionIds: string[]): Promise<MigrationPremapping[]> {
        const response = await this.apiService.httpClient.post<MigrationPremapping[]>(
            this.getActionPath('generate-premapping'),
            { dataSelectionIds },
            {
                headers: this.getHeaders(),
            },
        );

        return this.handleResponse(response) as MigrationPremapping[];
    }

    async writePremapping(premapping: MigrationPremapping[]): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('write-premapping'),
            { premapping },
            {
                headers: this.getHeaders(),
            },
        );

        return this.handleResponse(response);
    }

    async startMigration(dataSelectionNames: string[]): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('start-migration'),
            { dataSelectionNames },
            {
                headers: this.getHeaders(),
            },
        );

        return this.handleResponse(response);
    }

    async getState(): Promise<MigrationState> {
        const response = await this.apiService.httpClient.get<MigrationState>(this.getActionPath('get-state'), {
            headers: this.getHeaders(),
        });

        return this.handleResponse(response) as MigrationState;
    }

    async approveFinishedMigration(): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('approve-finished'),
            {},
            {
                headers: this.getHeaders(),
            },
        );

        return this.handleResponse(response);
    }

    async abortMigration(): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('abort-migration'),
            {},
            {
                headers: this.getHeaders(),
            },
        );

        return this.handleResponse(response);
    }

    async continueAfterErrorResolution(): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('resume-after-fixes'),
            {},
            {
                headers: this.getHeaders(),
            },
        );

        return this.handleResponse(response);
    }

    async getProfiles(): Promise<MigrationProfile[]> {
        const response = await this.apiService.httpClient.get<MigrationProfile[]>(this.getActionPath('get-profiles'), {
            headers: this.getHeaders(),
        });

        return this.handleResponse(response) as MigrationProfile[];
    }

    async getGateways(profileName: string): Promise<MigrationGateway[]> {
        const response = await this.apiService.httpClient.get<MigrationGateway[]>(this.getActionPath('get-gateways'), {
            headers: this.getHeaders(),
            params: {
                profileName,
            },
        });

        return this.handleResponse(response) as MigrationGateway[];
    }

    async getProfileInformation(profileName: string, gatewayName: string): Promise<MigrationProfile> {
        const response = await this.apiService.httpClient.get<MigrationProfile>(
            this.getActionPath('get-profile-information'),
            {
                headers: this.getHeaders(),
                params: {
                    profileName,
                    gatewayName,
                },
            },
        );

        return this.handleResponse(response) as MigrationProfile;
    }

    async getGroupedLogsOfRun(runUuid: string): Promise<GetGroupedLogsOfRunResponse> {
        const response = await this.apiService.httpClient.get<GetGroupedLogsOfRunResponse>(
            this.getActionPath('get-grouped-logs-of-run'),
            {
                headers: this.getHeaders(),
                params: {
                    runUuid,
                },
            },
        );

        return this.handleResponse(response) as GetGroupedLogsOfRunResponse;
    }

    async resetChecksums(connectionId: string, additionalHeaders: AdditionalHeaders = {}): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('reset-checksums'),
            {
                connectionId,
            },
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response);
    }

    async cleanupMigrationData(additionalHeaders: AdditionalHeaders = {}): Promise<ActionResponse> {
        const response = await this.apiService.httpClient.post<null>(
            this.getActionPath('cleanup-migration-data'),
            {},
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response);
    }

    async downloadLogsOfRun(runUuid: string, additionalHeaders: AdditionalHeaders = {}): Promise<Blob> {
        const response = await this.apiService.httpClient.post<Blob>(
            this.getActionPath('download-logs-of-run'),
            { runUuid },
            {
                headers: this.getHeaders(additionalHeaders),
                responseType: 'blob',
            },
        );

        return response.data;
    }

    async getLogGroups(
        runId: string,
        level: MigrationLogLevel,
        page: number,
        limit: number,
        sortBy: string,
        sortDirection: 'ASC' | 'DESC',
        filter: GetLogGroupsFilter = {},
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<GetLogGroupsResponse> {
        const params: Record<string, string | number> = {
            runId,
            level,
            page,
            limit,
            sortBy,
            sortDirection,
        };

        if (filter.code) {
            params.filterCode = filter.code;
        }

        if (filter.status) {
            params.filterStatus = filter.status;
        }

        if (filter.entity) {
            params.filterEntity = filter.entity;
        }

        if (filter.field) {
            params.filterField = filter.field;
        }

        const response = await this.apiService.httpClient.get<GetLogGroupsResponse>(this.getActionPath('get-log-groups'), {
            headers: this.getHeaders(additionalHeaders),
            params,
        });

        return this.handleResponse(response) as GetLogGroupsResponse;
    }

    async isResettingChecksums(): Promise<boolean> {
        const response = await this.apiService.httpClient.get<boolean>(this.getActionPath('is-resetting-checksums'), {
            headers: this.getHeaders(),
        });

        return this.handleResponse(response) as boolean;
    }

    async isTruncatingMigrationData(): Promise<boolean> {
        const response = await this.apiService.httpClient.get<boolean>(this.getActionPath('is-truncating-migration-data'), {
            headers: this.getHeaders(),
        });

        return this.handleResponse(response) as boolean;
    }

    async getUnresolvedLogsBatchInformation(
        runId: string,
        code: string,
        entityName: string,
        fieldName: string,
        connectionId?: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<GetUnresolvedLogsBatchInformationResponse> {
        const response = await this.apiService.httpClient.post<GetUnresolvedLogsBatchInformationResponse>(
            this.getActionPath('get-unresolved-logs-batch-information'),
            {
                runId,
                code,
                entityName,
                fieldName,
                connectionId,
            },
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response) as GetUnresolvedLogsBatchInformationResponse;
    }

    async getLogEntityIdsWithoutFix(
        runId: string,
        code: string,
        entityName: string,
        fieldName: string,
        limit?: number,
        connectionId?: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<LogEntityIdsWithoutFixResponse> {
        const response = await this.apiService.httpClient.post<LogEntityIdsWithoutFixResponse>(
            this.getActionPath('get-log-entity-ids-without-fix'),
            {
                runId,
                code,
                entityName,
                fieldName,
                limit,
                connectionId,
            },
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response) as LogEntityIdsWithoutFixResponse;
    }

    async validateResolution(
        entityName: string,
        fieldName: string,
        fieldValue: unknown,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<ValidateResolutionResponse> {
        const response = await this.apiService.httpClient.post<ValidateResolutionResponse>(
            this.getActionPath('error-resolution/validate'),
            {
                entityName,
                fieldName,
                fieldValue,
            },
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response) as ValidateResolutionResponse;
    }

    async getExampleFieldStructure(
        entityName: string,
        fieldName: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<GetExampleFieldStructureResponse> {
        const response = await this.apiService.httpClient.post<GetExampleFieldStructureResponse>(
            this.getActionPath('error-resolution/example-field-structure'),
            {
                entityName,
                fieldName,
            },
            {
                headers: this.getHeaders(additionalHeaders),
            },
        );

        return this.handleResponse(response) as GetExampleFieldStructureResponse;
    }
}
