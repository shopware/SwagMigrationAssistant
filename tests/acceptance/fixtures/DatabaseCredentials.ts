import { test as base, expect } from '@shopware-ag/acceptance-test-suite';
import type { FixtureTypes } from '@fixtures/AcceptanceTest';

export interface DatabaseCredentialsStruct {
    user: string;
    password: string;
    host: string;
    port: string;
    database: string;
}

export const DatabaseCredentials = base.extend<FixtureTypes>({
    DatabaseCredentials: async ({}, use) => {
        const dbUrl = process.env.DATABASE_URL;
        expect(dbUrl).toBeDefined();

        const url = new URL(dbUrl!);

        const credentials: DatabaseCredentialsStruct = {
            user: decodeURIComponent(url.username),
            password: decodeURIComponent(url.password),
            host: url.hostname,
            port: url.port,
            database: url.pathname.replace(/^\//, ''),
        };

        await use(credentials);
    },
});
