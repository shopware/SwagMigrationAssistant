import ApiService from 'src/core/service/api/api.service';

class MigrationApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'migration') {
        super(httpClient, loginService, apiEndpoint);
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

    startMigration(profileId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/start-migration`, { profileId }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    checkConnection(profileId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/check-connection`, { profileId }, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    fetchAssetUuids(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/fetch-media-uuids`, { params, headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    downloadAssets(additionalParams = {}, additionalHeaders = {}) {
        const params = additionalParams;
        const headers = this.getBasicHeaders(additionalHeaders);

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/download-assets`, params, {
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
}

export default MigrationApiService;
