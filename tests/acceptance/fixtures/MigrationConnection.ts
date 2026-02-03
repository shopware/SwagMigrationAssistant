import { test as base, expect } from '@shopware-ag/acceptance-test-suite';
import type { FixtureTypes } from './AcceptanceTest';

export interface MigrationConnectionStruct {
    id: string;
    name: string;
    profileName: string;
    gatewayName: string;
}

export const MigrationConnection = base.extend<FixtureTypes>({
    MigrationConnection: async ({ AdminApiContext, DatabaseCredentials }, use) => {
        const connectionName = 'shopware';

        const createResponse = await AdminApiContext.post('/api/swag-migration-connection', {
            data: {
                name: connectionName,
                profileName: 'shopware55',
                gatewayName: 'local',
            },
        });

        expect(createResponse.ok()).toBe(true);

        const searchResponse = await AdminApiContext.post('/api/search/swag-migration-connection', {
            data: {
                filter: [{ type: 'equals', field: 'name', value: connectionName }],
                limit: 1,
            },
        });

        expect(searchResponse.ok()).toBe(true);

        const searchData = await searchResponse.json();
        const connectionId = searchData.data[0].id;

        const credentialsResponse = await AdminApiContext.post('/api/_action/migration/update-connection-credentials', {
            data: {
                connectionId,
                credentialFields: {
                    dbHost: DatabaseCredentials.host,
                    dbPort: DatabaseCredentials.port,
                    dbUser: DatabaseCredentials.user,
                    dbPassword: DatabaseCredentials.password,
                    dbName: DatabaseCredentials.database,
                    installationRoot: '/tmp',
                },
            },
        });

        expect(credentialsResponse.ok()).toBe(true);

        const settingsSearchResponse = await AdminApiContext.post('/api/search/swag-migration-general-setting', {
            data: {
                limit: 1,
            },
        });

        expect(settingsSearchResponse.ok()).toBe(true);

        const settingsData = await settingsSearchResponse.json();
        const settingId = settingsData.data[0]?.id;

        expect(settingId).toBeDefined();

        const updateSettingsResponse = await AdminApiContext.patch(`/api/swag-migration-general-setting/${settingId}`, {
            data: {
                selectedConnectionId: connectionId,
            },
        });

        expect(updateSettingsResponse.ok()).toBe(true);

        const connection: MigrationConnectionStruct = {
            id: connectionId,
            name: connectionName,
            profileName: 'shopware55',
            gatewayName: 'local',
        };

        await use(connection);

        const deleteResponse = await AdminApiContext.delete(`/api/swag-migration-connection/${connectionId}`);
        expect(deleteResponse.ok()).toBe(true);
    },
});
