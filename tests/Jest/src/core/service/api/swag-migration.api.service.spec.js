/**
 * @sw-package after-sales
 */
import MigrationApiService, { MIGRATION_STEP } from 'SwagMigrationAssistant/core/service/api/swag-migration.api.service';
import createLoginService from 'src/core/service/login.service';
import createHTTPClient from 'src/core/factory/http.factory';
import MockAdapter from 'axios-mock-adapter';

function createMigrationApiService() {
    const client = createHTTPClient();
    const clientMock = new MockAdapter(client);
    const loginService = createLoginService(client, Shopware.Context.api);
    const migrationApiService = new MigrationApiService(client, loginService);

    clientMock.onAny().reply(200, {
        data: null,
    });

    return { migrationApiService, clientMock };
}

describe('src/core/service/api/swag-migration.api.service', () => {
    it('should registered correctly', async () => {
        const { migrationApiService } = createMigrationApiService();

        expect(migrationApiService).toBeInstanceOf(MigrationApiService);
    });

    it('should have the correct name', async () => {
        const { migrationApiService } = createMigrationApiService();

        expect(migrationApiService.name).toBe('migrationApiService');
    });

    it('should define migration steps', async () => {
        expect(MIGRATION_STEP).toEqual({
            IDLE: 'idle',
            FETCHING: 'fetching',
            ERROR_RESOLUTION: 'error-resolution',
            WRITING: 'writing',
            MEDIA_PROCESSING: 'media-processing',
            CLEANUP: 'cleanup',
            INDEXING: 'indexing',
            WAITING_FOR_APPROVE: 'waiting-for-approve',
            ABORTING: 'aborting',
        });
    });

    it('should update connection credentials', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            connectionId: '1234567890',
            credentialFields: {
                local: {
                    endpoint: 'http://shopware.local',
                },
            },
        };

        await migrationApiService.updateConnectionCredentials(data.connectionId, data.credentialFields, {
            'test-header': 'test-value',
        });

        expect(clientMock.history.post[0].url).toBe('_action/migration/update-connection-credentials');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
        expect(clientMock.history.post[0].headers['test-header']).toBe('test-value');
    });

    it('should check connection', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            connectionId: '1234567890',
            credentialFields: {
                local: {
                    endpoint: 'http://shopware.local',
                },
            },
        };

        await migrationApiService.checkConnection(data.connectionId, data.credentialFields, {
            'test-header': 'test-value',
        });

        expect(clientMock.history.post[0].url).toBe('_action/migration/check-connection');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
        expect(clientMock.history.post[0].headers['test-header']).toBe('test-value');
    });

    it('should get data selection', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            connectionId: '123456789',
        };

        await migrationApiService.getDataSelection(data.connectionId, {
            'test-header': 'test-value',
        });

        expect(clientMock.history.get[0].url).toBe('_action/migration/data-selection');
        expect(clientMock.history.get[0].params).toEqual(data);
        expect(clientMock.history.get[0].headers['test-header']).toBe('test-value');
    });

    it('should generate premapping', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            dataSelectionIds: ['1234567890'],
        };

        await migrationApiService.generatePremapping(data.dataSelectionIds);

        expect(clientMock.history.post[0].url).toBe('_action/migration/generate-premapping');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
    });

    it('should write premapping', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            premapping: [
                {
                    entity: 'product',
                    choices: [],
                    mapping: [],
                },
            ],
        };

        await migrationApiService.writePremapping(data.premapping);

        expect(clientMock.history.post[0].url).toBe('_action/migration/write-premapping');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
    });

    it('should start migration', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            dataSelectionNames: [
                'product',
                'category',
            ],
        };

        await migrationApiService.startMigration(data.dataSelectionNames);

        expect(clientMock.history.post[0].url).toBe('_action/migration/start-migration');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
    });

    it('should get state', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.getState();

        expect(clientMock.history.get[0].url).toBe('_action/migration/get-state');
    });

    it('should approve finished migration', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.approveFinishedMigration();

        expect(clientMock.history.post[0].url).toBe('_action/migration/approve-finished');
    });

    it('should abort migration', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.abortMigration();

        expect(clientMock.history.post[0].url).toBe('_action/migration/abort-migration');
    });

    it('should resume migration', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.continueAfterErrorResolution();

        expect(clientMock.history.post[0].url).toBe('_action/migration/resume-after-fixes');
    });

    it('should get profiles', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.getProfiles();

        expect(clientMock.history.get[0].url).toBe('_action/migration/get-profiles');
    });

    it('should get gateways', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            profileName: 'shopware6',
        };

        await migrationApiService.getGateways(data.profileName);

        expect(clientMock.history.get[0].url).toBe('_action/migration/get-gateways');
        expect(clientMock.history.get[0].params).toEqual(data);
    });

    it('should get profile information', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            profileName: 'shopware6',
            gatewayName: 'Shopware',
        };

        await migrationApiService.getProfileInformation(data.profileName, data.gatewayName);

        expect(clientMock.history.get[0].url).toBe('_action/migration/get-profile-information');
        expect(clientMock.history.get[0].params).toEqual(data);
    });

    it('should get grouped logs of run', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            runUuid: '123e4567-e89b-12d3-a456-426614174000',
        };

        await migrationApiService.getGroupedLogsOfRun(data.runUuid);

        expect(clientMock.history.get[0].url).toBe('_action/migration/get-grouped-logs-of-run');
        expect(clientMock.history.get[0].params).toEqual(data);
    });

    it('should clear data of run', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            runUuid: '123e4567-e89b-12d3-a456-426614174000',
        };

        await migrationApiService.clearDataOfRun(data.runUuid);

        expect(clientMock.history.post[0].url).toBe('_action/migration/clear-data-of-run');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
    });

    it('should reset checksums', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            connectionId: '123456789',
        };

        await migrationApiService.resetChecksums(data.connectionId, {
            'test-header': 'test-value',
        });

        expect(clientMock.history.post[0].url).toBe('_action/migration/reset-checksums');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
        expect(clientMock.history.post[0].headers['test-header']).toBe('test-value');
    });

    it('should cleanup migration data', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.cleanupMigrationData({
            'test-header': 'test-value',
        });

        expect(clientMock.history.post[0].url).toBe('_action/migration/cleanup-migration-data');
        expect(clientMock.history.post[0].headers['test-header']).toBe('test-value');
    });

    it('should check if media is processing', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.isMediaProcessing({
            'test-header': 'test-value',
        });

        expect(clientMock.history.get[0].url).toBe('_action/migration/is-media-processing');
        expect(clientMock.history.get[0].headers['test-header']).toBe('test-value');
    });

    it('should download logs of run', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            responseType: 'blob',
            runId: '123456789',
        };

        await migrationApiService.downloadLogsOfRun(data.runId, {
            'test-header': 'test-value',
        });

        expect(clientMock.history.post[0].url).toBe('_action/migration/download-logs-of-run');
        expect(clientMock.history.post[0].responseType).toBe(data.responseType);
        expect(clientMock.history.post[0].headers['test-header']).toBe('test-value');
    });

    it('should get log groups', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const filter = {
            code: 'MIGRATION_001',
            status: 'resolved',
            entity: 'product',
            field: 'name',
        };

        const params = {
            runId: '123456789',
            level: 'error',
            page: 1,
            limit: 25,
            sortBy: 'timestamp',
            sortDirection: 'DESC',
            filterCode: filter.code,
            filterStatus: filter.status,
            filterEntity: filter.entity,
            filterField: filter.field,
        };

        await migrationApiService.getLogGroups(
            params.runId,
            params.level,
            params.page,
            params.limit,
            params.sortBy,
            params.sortDirection,
            filter,
            {
                'test-header': 'test-value',
            },
        );

        expect(clientMock.history.get[0].url).toBe('_action/migration/get-log-groups');
        expect(clientMock.history.get[0].params).toEqual(params);
        expect(clientMock.history.get[0].headers['test-header']).toBe('test-value');
    });

    it('should check is resetting checksums', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.isResettingChecksums();

        expect(clientMock.history.get[0].url).toBe('_action/migration/is-resetting-checksums');
    });

    it('should check is truncating migration data', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        await migrationApiService.isTruncatingMigrationData();

        expect(clientMock.history.get[0].url).toBe('_action/migration/is-truncating-migration-data');
    });

    it('should get all entity ids', async () => {
        const { migrationApiService, clientMock } = createMigrationApiService();

        const data = {
            runId: '987654321',
            code: 'MIGRATION_001',
            entityName: 'product',
            fieldName: 'name',
            connectionId: '123456789',
            limit: 10,
        };

        await migrationApiService.getAllEntityIds(
            data.runId,
            data.code,
            data.entityName,
            data.fieldName,
            data.connectionId,
            data.limit,
            { 'test-header': 'test-value' },
        );

        expect(clientMock.history.post[0].url).toBe('_action/migration/get-all-entity-ids');
        expect(clientMock.history.post[0].data).toBe(JSON.stringify(data));
        expect(clientMock.history.post[0].headers['test-header']).toBe('test-value');
    });
});
