import type { AxiosInstance, AxiosResponse } from 'axios';
import type { LoginService } from '@administration/src/core/service/login.service';
import type { ApiResponse } from '@administration/src/core/service/api.service';
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

type AdditionalHeaders = Record<string, string>;

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
 */
export type LogGroup = {
    code: string;
    entityName: string | null;
    fieldName: string | null;
    count: number;
};

/**
 * @private
 */
export type LogLevelCounts = {
    error: number;
    warning: number;
    info: number;
};

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default class MigrationApiService extends ApiService {
    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'migration') {
        super(httpClient, loginService, apiEndpoint);
        // @ts-ignore
        this.name = MIGRATION_API_SERVICE;
    }

    async createNewConnection(
        connectionId: string,
        connectionName: string,
        profileName: string,
        gatewayName: string,
        credentialFields: MigrationCredentials,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<ApiResponse<MigrationEnvironmentInformation>> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/create-new-connection`,
                {
                    connectionId,
                    connectionName,
                    profileName,
                    gatewayName,
                    credentialFields,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response as AxiosResponse);
            });
    }

    async updateConnectionCredentials(
        connectionId: string,
        credentialFields: MigrationCredentials,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<ApiResponse<MigrationEnvironmentInformation>> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/update-connection-credentials`,
                {
                    connectionId,
                    credentialFields,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response as AxiosResponse);
            });
    }

    async checkConnection(
        connectionId: string,
        credentialFields?: Record<string, string>,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<MigrationEnvironmentInformation> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        const payload: { connectionId: string; credentialFields?: Record<string, string> } = { connectionId };

        if (credentialFields) {
            payload.credentialFields = credentialFields;
        }

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/check-connection`,
                payload,
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getDataSelection(
        connectionId: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<MigrationDataSelection[]> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/data-selection`, {
                    params: {
                        connectionId,
                    },
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async generatePremapping(dataSelectionIds: string[]): Promise<MigrationPremapping[]> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/generate-premapping`,
                { dataSelectionIds },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async writePremapping(premapping: MigrationPremapping[]): Promise<ApiResponse<unknown>> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/write-premapping`,
                { premapping },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async startMigration(dataSelectionNames: string[]): Promise<ApiResponse<unknown>> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/start-migration`,
                {
                    dataSelectionNames,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getState(): Promise<MigrationState> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-state`, {
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async approveFinishedMigration(): Promise<ApiResponse<unknown>> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/approve-finished`,
                {},
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async abortMigration(): Promise<ApiResponse<unknown>> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/abort-migration`,
                {},
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async continueAfterErrorResolution(): Promise<ApiResponse<unknown>> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/resume-after-fixes`,
                {},
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getProfiles(): Promise<MigrationProfile[]> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-profiles`, {
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async getGateways(profileName: string): Promise<MigrationGateway[]> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-gateways`, {
                    params: {
                        profileName,
                    },
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async getProfileInformation(profileName: string, gatewayName: string): Promise<MigrationProfile> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-profile-information`, {
                    params: {
                        profileName,
                        gatewayName,
                    },
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async getGroupedLogsOfRun(runUuid: string): Promise<{
        total: number;
        downloadUrl: string;
        items: MigrationError[];
    }> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-grouped-logs-of-run`, {
                    params: {
                        runUuid,
                    },
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async resetChecksums(connectionId: string, additionalHeaders: AdditionalHeaders = {}): Promise<ApiResponse<unknown>> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/reset-checksums`,
                {
                    connectionId,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async cleanupMigrationData(additionalHeaders: AdditionalHeaders = {}): Promise<ApiResponse<unknown>> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient.post(
            // @ts-ignore
            `_action/${this.getApiBasePath()}/cleanup-migration-data`,
            {},
            {
                headers,
            },
        );
    }

    async downloadLogsOfRun(runUuid: string, additionalHeaders: AdditionalHeaders = {}): Promise<Blob> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/download-logs-of-run`,
                { runUuid },
                {
                    headers,
                    responseType: 'blob',
                },
            )
            .then((response: AxiosResponse<Blob>) => {
                return response.data;
            });
    }

    async getLogGroups(
        runId: string,
        level: string,
        page: number,
        limit: number,
        sortBy: string,
        sortDirection: 'ASC' | 'DESC',
        filter: {
            code: string | null;
            status: 'resolved' | 'unresolved' | null;
            entity: string | null;
            field: string | null;
        },
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<{
        total: number;
        items: LogGroup[];
        levelCounts: LogLevelCounts;
    }> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

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

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-log-groups`, {
                    params,
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async isResettingChecksums(): Promise<boolean> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/is-resetting-checksums`, {
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async isTruncatingMigrationData(): Promise<boolean> {
        // @ts-ignore
        const headers = this.getBasicHeaders();

        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/is-truncating-migration-data`, {
                    headers,
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async getUnresolvedLogsBatchInformation(
        runId: string,
        code: string,
        entityName: string,
        fieldName: string,
        connectionId?: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<{ count: number; limit: number }> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/get-unresolved-logs-batch-information`,
                {
                    runId,
                    code,
                    entityName,
                    fieldName,
                    connectionId,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getLogEntityIdsWithoutFix(
        runId: string,
        code: string,
        entityName: string,
        fieldName: string,
        limit?: number,
        connectionId?: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<{ entityIds: string[] }> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/get-log-entity-ids-without-fix`,
                {
                    runId,
                    code,
                    entityName,
                    fieldName,
                    limit,
                    connectionId,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async validateResolution(
        entityName: string,
        fieldName: string,
        fieldValue: unknown,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<{ isValid: boolean; violations: Array<{ message: string; propertyPath?: string }> }> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/error-resolution/validate`,
                {
                    entityName,
                    fieldName,
                    fieldValue,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getExampleFieldStructure(
        entityName: string,
        fieldName: string,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<{ fieldType: string; example: string | null }> {
        // @ts-ignore
        const headers = this.getBasicHeaders(additionalHeaders);

        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/error-resolution/example-field-structure`,
                {
                    entityName,
                    fieldName,
                },
                {
                    headers,
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }
}
