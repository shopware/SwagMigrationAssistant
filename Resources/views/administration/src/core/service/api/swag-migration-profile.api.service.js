import ApiService from 'src/core/service/api/api.service';

class MigrationProfileService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-migration-profile') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default MigrationProfileService;
