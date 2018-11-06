import ApiService from 'src/core/service/api/api.service';

class MigrationDataService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-migration-data') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default MigrationDataService;
