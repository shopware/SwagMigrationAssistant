import { test as base, expect } from '@shopware-ag/acceptance-test-suite';
import {FixtureTypes} from './AcceptanceTest';

export interface DatabaseCredentialsStruct {
    user: string,
    password: string,
    host: string,
    port: string,
    database: string,
}

export const DatabaseCredentials = base.extend<FixtureTypes>({
    DatabaseCredentials: [async ({ }, use) => {
        const dbUrl = process.env.DATABASE_URL;
        const match = dbUrl.match(/\/\/(.+):(.+)@(.+):(.+)\/(.+)/);
        expect(match.length).toBeGreaterThanOrEqual(5);
        const credentials = {
            user: match[1],
            password: match[2],
            host: match[3],
            port: match[4],
            database: 'sw55',
        } as DatabaseCredentialsStruct;

        await use(credentials);
    }, { scope: 'worker' }],
});
