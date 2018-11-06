import ApiService from 'src/core/service/api/api.service';

class MigrationLoggingService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-migration-logging') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default MigrationLoggingService;
