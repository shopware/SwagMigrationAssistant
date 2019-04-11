import ApiService from 'src/core/service/api.service';

class MigrationApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'migration') {
        super(httpClient, loginService, apiEndpoint);
    }

    updateConnectionCredentials(connectionId, credentialFields, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/update-connection-credentials`, {
                connectionId,
                credentialFields
            }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    checkConnection(connectionId, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/check-connection`, { connectionId }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getDataSelection(connectionId, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/data-selection`, {
                params: {
                    connectionId
                },
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    createMigration(connectionId, dataSelectionIds) {
        const params = {
            connectionId,
            dataSelectionIds
        };
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/create-migration`, params, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    fetchData(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/fetch-data`, params, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    updateWriteProgress(runUuid, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/update-write-progress`, {
                runUuid
            }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    updateMediaFilesProgress(runUuid, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/update-media-files-progress`, {
                runUuid
            }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    writeData(additionalParams = {}, additionalHeaders = { }) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/write-data`, params, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    fetchMediaUuids(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/fetch-media-uuids`, { params, headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    processMedia(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/process-media`, params, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getState(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/get-state`, params, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    takeoverMigration(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/takeover-migration`, { runUuid }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    abortMigration(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/abort-migration`, { runUuid }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    generatePremapping(runUuid) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/generate-premapping`, { runUuid }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    writePremapping(runUuid, premapping) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/write-premapping`, { runUuid, premapping }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default MigrationApiService;
