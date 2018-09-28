import ApiService from 'src/core/service/api/api.service';

class DataService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-migration-media-file') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default DataService;
