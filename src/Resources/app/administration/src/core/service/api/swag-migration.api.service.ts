import type { AxiosInstance, AxiosResponse, AxiosRequestConfig } from 'axios';
import type { LoginService } from '@administration/src/core/service/login.service';
import type {
    CredentialFields,
    DataSelection,
    EnvironmentInformation,
    MigrationGateway,
    MigrationProfile,
    MigrationState,
    Premapping,
} from '../../../type/types';

type AdditionalHeaders = Record<string, string>;

const ApiService = Shopware.Classes.ApiService;

export const migrationApiServiceName = 'migrationApiService';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export const MIGRATION_STEP = {
    IDLE: 'idle',
    FETCHING: 'fetching',
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
    private readonly basicConfig: AxiosRequestConfig & { version: string };

    constructor(httpClient: AxiosInstance, loginService: LoginService, apiEndpoint = 'migration') {
        super(httpClient, loginService, apiEndpoint);
        // @ts-ignore
        this.name = migrationApiServiceName;
        this.basicConfig = {
            timeout: 30000,
            version: Shopware.Context.api.apiVersion,
        };
    }

    async updateConnectionCredentials(
        connectionId: string,
        credentialFields: CredentialFields,
        additionalHeaders: AdditionalHeaders = {},
    ): Promise<unknown> {
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
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(additionalHeaders),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response as AxiosResponse);
            });
    }

    async checkConnection(connectionId: string, additionalHeaders: AdditionalHeaders = {}): Promise<EnvironmentInformation> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/check-connection`,
                { connectionId },
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(additionalHeaders),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getDataSelection(connectionId: string, additionalHeaders: AdditionalHeaders = {}): Promise<DataSelection[]> {
        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/data-selection`, {
                    ...this.basicConfig,
                    params: {
                        connectionId,
                    },
                    // @ts-ignore
                    ...this.getBasicHeaders(additionalHeaders),
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async generatePremapping(dataSelectionIds: string[]): Promise<Premapping> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/generate-premapping`,
                { dataSelectionIds },
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async writePremapping(premapping: Premapping[]): Promise<unknown> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/write-premapping`,
                { premapping },
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async startMigration(dataSelectionNames: string[]): Promise<unknown> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/start-migration`,
                {
                    dataSelectionNames,
                },
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getState(): Promise<MigrationState> {
        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-state`, {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async approveFinishedMigration(): Promise<unknown> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/approve-finished`,
                {},
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async abortMigration(): Promise<unknown> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/abort-migration`,
                {},
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async getProfiles(): Promise<MigrationProfile[]> {
        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-profiles`, {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async getGateways(profileName: string): Promise<MigrationGateway[]> {
        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-gateways`, {
                    ...this.basicConfig,
                    params: {
                        profileName,
                    },
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async getProfileInformation(profileName: string, gatewayName: string): Promise<MigrationProfile> {
        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`_action/${this.getApiBasePath()}/get-profile-information`, {
                    ...this.basicConfig,
                    params: {
                        profileName,
                        gatewayName,
                    },
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async getGroupedLogsOfRun(runUuid: string): Promise<{
        total: number;
        downloadUrl: string;
        item: {
            code: string;
            count: number;
            titleSnippet: string;
            entity: string;
            level: string;
        }[];
    }> {
        return (
            // @ts-ignore
            this.httpClient
                // @ts-ignore
                .get(`${this.getApiBasePath()}/get-grouped-logs-of-run`, {
                    ...this.basicConfig,
                    params: {
                        runUuid,
                    },
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                })
                .then((response: AxiosResponse) => {
                    return ApiService.handleResponse(response);
                })
        );
    }

    async clearDataOfRun(runUuid: string): Promise<unknown> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/clear-data-of-run`,
                {
                    runUuid,
                },
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async resetChecksums(connectionId: string, additionalHeaders: AdditionalHeaders = {}): Promise<unknown> {
        // @ts-ignore
        return this.httpClient
            .post(
                // @ts-ignore
                `_action/${this.getApiBasePath()}/reset-checksums`,
                {
                    connectionId,
                },
                {
                    ...this.basicConfig,
                    // @ts-ignore
                    ...this.getBasicHeaders(additionalHeaders),
                },
            )
            .then((response: AxiosResponse) => {
                return ApiService.handleResponse(response);
            });
    }

    async cleanupMigrationData(additionalHeaders: AdditionalHeaders = {}): Promise<unknown> {
        // @ts-ignore
        return this.httpClient.post(`_action/${this.getApiBasePath()}/cleanup-migration-data`, {
            ...this.basicConfig,
            // @ts-ignore
            ...this.getBasicHeaders(additionalHeaders),
        });
    }

    async isMediaProcessing(additionalHeaders: AdditionalHeaders = {}): Promise<boolean> {
        // @ts-ignore
        return this.httpClient.get(`_action/${this.getApiBasePath()}/is-media-processing`, {
            ...this.basicConfig,
            // @ts-ignore
            ...this.getBasicHeaders(additionalHeaders),
        });
    }
}
