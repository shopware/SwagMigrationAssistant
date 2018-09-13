import ApiService from 'src/core/service/api/api.service';

class RunService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-migration-run') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default RunService;
