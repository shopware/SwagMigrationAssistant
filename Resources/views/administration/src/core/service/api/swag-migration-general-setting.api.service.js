import ApiService from 'src/core/service/api/api.service';

class MigrationGeneralSettingService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-migration-general-setting') {
        super(httpClient, loginService, apiEndpoint);
    }
}

export default MigrationGeneralSettingService;
