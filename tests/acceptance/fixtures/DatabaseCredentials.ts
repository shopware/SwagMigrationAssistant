import { test as base, expect } from '@shopware-ag/acceptance-test-suite';
import type { FixtureTypes } from './AcceptanceTest';

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

        const match = /\/\/(.+):(.+)@(.+):(.+)\/(.+)/.exec(dbUrl!);
        expect(match).not.toBeNull();
        expect(match!.length).toBeGreaterThanOrEqual(5);

        const credentials: DatabaseCredentialsStruct = {
            user: match![1],
            password: match![2],
            host: match![3],
            port: match![4],
            database: match![5],
        };

        await use(credentials);
    },
});
