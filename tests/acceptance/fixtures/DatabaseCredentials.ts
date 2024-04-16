import { test as base, expect } from '@acceptance/fixtures/AcceptanceTest';

export const DatabaseCredentials = base.extend({
    databaseCredentials: [async ({ }, use) => {
        const dbUrl = process.env['DATABASE_URL'];
        const match = dbUrl.match(/\/\/(.+):(.+)@(.+):(.+)\/(.+)/);
        expect(match.length).toBeGreaterThanOrEqual(5);
        const credentials = {
            user: match[1],
            password: match[2],
            host: match[3],
            port: match[4],
            database: 'sw55',
        };

        await use(credentials);
    }, { scope: 'worker' }],
});
