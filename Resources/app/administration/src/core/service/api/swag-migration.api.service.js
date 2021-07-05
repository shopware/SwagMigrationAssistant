const ApiService = Shopware.Classes.ApiService;

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

    createMigration(connectionId, dataSelectionIds) {
        const params = {
            connectionId,
            dataSelectionIds,
        };
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/create-migration`, params, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    fetchData(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/fetch-data`, params, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    updateWriteProgress(runUuid, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/update-write-progress`, {
            runUuid,
        }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    updateMediaFilesProgress(runUuid, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/update-media-files-progress`, {
            runUuid,
        }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    writeData(additionalParams = {}, additionalHeaders = { }) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/write-data`, params, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    processMedia(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/process-media`, params, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getState(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient.post(`_action/${this.getApiBasePath()}/get-state`, params, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    takeoverMigration(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/takeover-migration`, { runUuid }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    abortMigration(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/abort-migration`, { runUuid }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    finishMigration(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/finish-migration`, { runUuid }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    assignThemes(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/assign-themes`, { runUuid }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    generatePremapping(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/generate-premapping`, { runUuid }, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    writePremapping(runUuid, premapping) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(`_action/${this.getApiBasePath()}/write-premapping`, { runUuid, premapping }, {
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

    indexing(additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);
        return this.httpClient.post('_action/index', {
            ...this.basicConfig,
            headers,
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

export default MigrationApiService;
