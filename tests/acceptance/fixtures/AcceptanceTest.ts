import { test as ShopwareTestSuite, mergeTests } from '@shopware-ag/acceptance-test-suite';
import type { FixtureTypes as BaseTypes } from '@shopware-ag/acceptance-test-suite';

import { DatabaseCredentials, type DatabaseCredentialsStruct } from './DatabaseCredentials';
import { EntityCounter, type EntityCounterStruct } from './EntityCounter';
import { MigrationConnection, type MigrationConnectionStruct } from './MigrationConnection';

export * from '@shopware-ag/acceptance-test-suite';

export interface MigrationFixtureTypes {
    DatabaseCredentials: DatabaseCredentialsStruct;
    EntityCounter: EntityCounterStruct;
    MigrationConnection: MigrationConnectionStruct;
}

export type FixtureTypes = MigrationFixtureTypes & BaseTypes;

export const test = mergeTests(ShopwareTestSuite, DatabaseCredentials, EntityCounter, MigrationConnection);
