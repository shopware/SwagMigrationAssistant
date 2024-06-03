const ApiService = Shopware.Classes.ApiService;

/**
 * @private
 * @package services-settings
 */
export const MIGRATION_STEP = Object.freeze({
    IDLE: 'idle',
    FETCHING: 'fetching',
    WRITING: 'writing',
    MEDIA_PROCESSING: 'media-processing',
    CLEANUP: 'cleanup',
    INDEXING: 'indexing',
    WAITING_FOR_APPROVE: 'waiting-for-approve',
    ABORTING: 'aborting',
});

/**
 * @private
 * @package services-settings
 */
class MigrationApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'migration') {
        super(httpClient, loginService, apiEndpoint);
        this.basicConfig = {
            timeout: 30000,
            version: Shopware.Context.api.apiVersion,
        };
    }

    updateConnectionCredentials(connectionId, credentialFields, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/update-connection-credentials`, {
            connectionId,
            credentialFields,
        }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    checkConnection(connectionId, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/check-connection`, { connectionId }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDataSelection(connectionId, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.get(`_action/${this.getApiBasePath()}/data-selection`, {
            ...this.basicConfig,
            params: {
                connectionId,
            },
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * @param {string[]} dataSelectionIds
     */
    generatePremapping(dataSelectionIds) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/generate-premapping`, { dataSelectionIds }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    writePremapping(premapping) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/write-premapping`, { premapping }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * @param {string[]} dataSelectionNames
     */
    startMigration(dataSelectionNames) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/start-migration`, {
            dataSelectionNames,
        }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getState() {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(`_action/${this.getApiBasePath()}/get-state`, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    approveFinishedMigration() {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/approve-finished`, {}, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    abortMigration() {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/abort-migration`, {}, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getProfiles() {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(`_action/${this.getApiBasePath()}/get-profiles`, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getGateways(profileName) {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(`_action/${this.getApiBasePath()}/get-gateways`, {
            ...this.basicConfig,
            params: {
                profileName,
            },
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getProfileInformation(profileName, gatewayName) {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(`_action/${this.getApiBasePath()}/get-profile-information`, {
            ...this.basicConfig,
            params: {
                profileName,
                gatewayName,
            },
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getGroupedLogsOfRun(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient.get(`${this.getApiBasePath()}/get-grouped-logs-of-run`, {
            ...this.basicConfig,
            params: {
                runUuid,
            },
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    clearDataOfRun(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/clear-data-of-run`, {
            runUuid,
        }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    resetChecksums(connectionId, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/reset-checksums`, {
            connectionId,
        }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    cleanupMigrationData(additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);
        return this.httpClient.post(`_action/${this.getApiBasePath()}/cleanup-migration-data`, {
            ...this.basicConfig,
            headers,
        });
    }

    isMediaProcessing(additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);
        return this.httpClient.get(`_action/${this.getApiBasePath()}/is-media-processing`, {
            ...this.basicConfig,
            headers,
        });
    }
}

/**
 * @private
 * @package services-settings
 */
export default MigrationApiService;
