import { randomUUID } from 'node:crypto';
import { config } from 'src/lib/config.ts';
import { debugLog, fetchWithTimeout, normalizeUrl, retry } from 'src/lib/utils.ts';
import {
    DataSelection,
    GroupedLogResponse,
    LogGroupsResponse,
    MigrationState,
    PremappingGroup,
    RunEntity,
} from 'src/lib/types.ts';

interface SearchResponse<T> {
    data: T[];
    total?: number;
    aggregations?: Record<string, { count?: number }>;
}

interface ApiError {
    detail?: string;
    title?: string;
}

interface AdminLoginResponse {
    access_token?: string;
    errors?: ApiError[];
}

interface EntityId {
    id: string;
}

export class TargetAdminApiClient {
    private readonly endpoint: string;
    private token: string | null = null;

    constructor(
        endpoint: string,
        private readonly username: string,
        private readonly password: string,
    ) {
        this.endpoint = normalizeUrl(endpoint);
    }

    async waitForReady(): Promise<void> {
        await retry(
            async () => {
                const response = await fetchWithTimeout(
                    `${this.endpoint}${config.apiPath.version}`,
                    undefined,
                    config.requestTimeoutMs,
                    `Target readiness check ${config.apiPath.version}`,
                );

                if (response.status >= 500) {
                    throw new Error(`Target system not ready yet: ${response.status}`);
                }

                if (!response.ok && response.status !== 401) {
                    debugLog(`--- Target API is reachable and responded with ${response.status} before authentication`);
                }
            },
            config.polling.readinessAttempts,
            config.polling.readinessIntervalMs,
        );
    }

    async login(): Promise<void> {
        const response = await fetchWithTimeout(
            `${this.endpoint}${config.apiPath.oauth}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    grant_type: config.auth.grantType,
                    client_id: config.auth.clientId,
                    username: this.username,
                    password: this.password,
                }),
            },
            config.requestTimeoutMs,
            `Target admin login ${config.apiPath.oauth}`,
        );

        const payload = (await response.json()) as AdminLoginResponse;

        if (!response.ok || !payload.access_token) {
            throw new Error(`Target admin login failed: ${extractApiError(payload.errors) ?? 'Unknown login failure'}`);
        }

        this.token = payload.access_token;
    }

    async createMigrationConnection(
        connectionName: string,
        profileName: string,
        gatewayName: string,
        credentialFields: Record<string, string>,
    ): Promise<string> {
        const connectionId = randomUUID().replaceAll('-', '');

        await this.request(config.apiPath.createConnection, {
            method: 'POST',
            body: {
                connectionId,
                connectionName,
                profileName,
                gatewayName,
                credentialFields,
            },
        });

        return connectionId;
    }

    async checkConnection(connectionId: string): Promise<void> {
        await this.request(config.apiPath.checkConnection, {
            method: 'POST',
            body: { connectionId },
        });
    }

    async setSelectedConnection(connectionId: string): Promise<void> {
        const settings = await this.search<EntityId>(config.entityName.generalSetting, {
            limit: 1,
        });

        const settingId = settings.data[0]?.id;

        if (!settingId) {
            throw new Error(`Unable to find ${config.entityName.generalSetting} entry.`);
        }

        await this.request(config.apiPath.getGeneralSetting(settingId), {
            method: 'PATCH',
            body: { selectedConnectionId: connectionId },
        });
    }

    async getDataSelections(connectionId: string): Promise<DataSelection[]> {
        return this.request<DataSelection[]>(config.apiPath.getDataSelections(connectionId), {
            method: 'GET',
        });
    }

    async generateAndWritePremapping(dataSelectionIds: string[]): Promise<void> {
        const groups = await this.request<PremappingGroup[]>(config.apiPath.generatePremapping, {
            method: 'POST',
            body: { dataSelectionIds },
        });

        const filled = groups.map((group) => ({
            ...group,
            mapping: group.mapping.map((entry) => ({
                ...entry,
                destinationUuid: entry.destinationUuid || (group.choices[0]?.uuid ?? ''),
            })),
        }));

        await this.request(config.apiPath.writePremapping, {
            method: 'POST',
            body: { premapping: filled },
            allowEmptyResponse: true,
        });
    }

    async startMigration(dataSelectionIds: string[]): Promise<void> {
        await this.request(config.apiPath.startMigration, {
            method: 'POST',
            body: { dataSelectionNames: dataSelectionIds },
            allowEmptyResponse: true,
        });
    }

    async getMigrationState(): Promise<MigrationState> {
        return this.request<MigrationState>(config.apiPath.getState, {
            method: 'GET',
        });
    }

    async approveFinishedMigration(): Promise<void> {
        await this.request(config.apiPath.approveFinished, {
            method: 'POST',
            body: {},
            allowEmptyResponse: true,
        });
    }

    async continueAfterErrorResolution(): Promise<void> {
        await this.request(config.apiPath.resumeAfterFixes, {
            method: 'POST',
            body: {},
            allowEmptyResponse: true,
        });
    }

    async getLatestRun(connectionId: string): Promise<RunEntity | null> {
        const runs = await this.search<RunEntity>(config.entityName.migrationRun, {
            limit: 1,
            sort: [{ field: 'createdAt', order: 'DESC' }],
            filter: [{ type: 'equals', field: 'connectionId', value: connectionId }],
        });

        return runs.data[0] ?? null;
    }

    async getGroupedLogsOfRun(runId: string): Promise<GroupedLogResponse> {
        return this.request<GroupedLogResponse>(config.apiPath.getGroupedLogs(runId), {
            method: 'GET',
        });
    }

    async getUnresolvedErrorCount(runId: string): Promise<number> {
        const params = new URLSearchParams({
            runId,
            level: 'error',
            page: '1',
            limit: '1',
            sortBy: 'count',
            sortDirection: 'DESC',
            filterStatus: 'unresolved',
        });

        const response = await this.request<LogGroupsResponse>(`${config.apiPath.getLogGroups}?${params.toString()}`, {
            method: 'GET',
        });

        return response.levelCounts.error;
    }

    async downloadLogsOfRun(runId: string): Promise<string> {
        const response = await fetchWithTimeout(
            `${this.endpoint}${config.apiPath.downloadLogs}`,
            {
                method: 'POST',
                headers: this.headers(true),
                body: JSON.stringify({ runUuid: runId }),
            },
            config.requestTimeoutMs,
            `Download logs for run ${runId}`,
        );

        if (!response.ok) {
            throw new Error(`Failed to download logs for run ${runId}: ${response.status} ${await response.text()}`);
        }

        return response.text();
    }

    async searchCount(entityName: string): Promise<number> {
        const response = await this.search(entityName, {
            limit: 1,
            includes: {
                [entityName]: ['id'],
            },
            aggregations: [
                {
                    name: 'entityCount',
                    type: 'count',
                    field: 'id',
                },
            ],
        });

        return response.aggregations?.entityCount?.count ?? 0;
    }

    private async search<T>(entityName: string, criteria: Record<string, unknown>): Promise<SearchResponse<T>> {
        return this.request<SearchResponse<T>>(config.apiPath.search(toApiEntityName(entityName)), {
            method: 'POST',
            body: criteria,
        });
    }

    private async request<T>(
        path: string,
        options: {
            method: 'GET' | 'POST' | 'PATCH';
            body?: Record<string, unknown>;
            allowEmptyResponse?: boolean;
        },
    ): Promise<T> {
        const response = await fetchWithTimeout(
            `${this.endpoint}${path}`,
            {
                method: options.method,
                headers: this.headers(options.method !== 'GET'),
                body: options.body ? JSON.stringify(options.body) : undefined,
            },
            config.requestTimeoutMs,
            `Target request ${options.method} ${path}`,
        );

        if (options.allowEmptyResponse && response.status === 204) {
            return undefined as T;
        }

        const contentType = response.headers.get('content-type') ?? '';
        const isJson = contentType.includes('application/json');

        const payload = isJson ? ((await response.json()) as Record<string, unknown>) : await response.text();

        if (response.ok) {
            return payload as T;
        }

        if (typeof payload === 'string') {
            throw new Error(`Request ${options.method} ${path} failed: ${payload}`);
        }

        const errors = Array.isArray(payload.errors) ? (payload.errors as ApiError[]) : null;
        const errorMessage = extractApiError(errors) ?? JSON.stringify(payload);

        throw new Error(`Request ${options.method} ${path} failed: ${errorMessage}`);
    }

    private headers(withBody: boolean): Record<string, string> {
        if (!this.token) {
            throw new Error('Target admin client is not logged in.');
        }

        const headers: Record<string, string> = {
            Accept: 'application/json',
            Authorization: `Bearer ${this.token}`,
        };

        if (withBody) {
            headers['Content-Type'] = 'application/json';
        }

        return headers;
    }
}

function extractApiError(errors: ApiError[] | null | undefined): string | undefined {
    return errors?.[0]?.detail ?? errors?.[0]?.title;
}

function toApiEntityName(entityName: string): string {
    return entityName.replaceAll('_', '-');
}
