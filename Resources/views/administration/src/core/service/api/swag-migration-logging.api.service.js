import ApiService from 'src/core/service/api/api.service';

class LoggingService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-migration-logging') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default LoggingService;
