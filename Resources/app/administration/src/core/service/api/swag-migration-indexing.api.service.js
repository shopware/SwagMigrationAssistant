const ApiService = Shopware.Classes.ApiService;

class MigrationIndexingApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'migration') {
        super(httpClient, loginService, apiEndpoint);
        this.basicConfig = {
            timeout: 30000,
        };
    }

    indexing(lastIndexer = null, offset = null, timestamp = null, additionalHeaders = {}) {
        const headers = this.getBasicHeaders(additionalHeaders);

        const params = {};
        if (lastIndexer !== null && lastIndexer !== undefined) {
            params.lastIndexer = lastIndexer;
        }
        if (offset !== null && offset !== undefined) {
            params.offset = offset;
        }
        if (timestamp !== null && timestamp !== undefined) {
            params.timestamp = timestamp;
        }

        return this.httpClient.post(`_action/${this.getApiBasePath()}/indexing`, params, {
            ...this.basicConfig,
            headers,
        }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default MigrationIndexingApiService;
