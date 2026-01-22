import { test as ShopwareTestSuite, mergeTests } from '@shopware-ag/acceptance-test-suite';
import type { FixtureTypes as BaseTypes } from '@shopware-ag/acceptance-test-suite';

import { MigrationUser } from './MigrationUser';
import { DatabaseCredentials, DatabaseCredentialsStruct } from './DatabaseCredentials';
import { EntityCounter, EntityCounterStruct } from './EntityCounter';

export * from '@shopware-ag/acceptance-test-suite';

export interface MigrationFixtureTypes {
    MigrationUser: FixtureTypes['ShopAdmin'];
    DatabaseCredentials: DatabaseCredentialsStruct;
    EntityCounter: EntityCounterStruct;
}

export type FixtureTypes = MigrationFixtureTypes & BaseTypes;

export const test = mergeTests(
    ShopwareTestSuite,
    MigrationUser,
    DatabaseCredentials,
    EntityCounter,
);
