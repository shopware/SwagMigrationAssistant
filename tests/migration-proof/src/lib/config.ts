import { resolve } from 'node:path';
import { env, isEnabled } from 'src/lib/utils.ts';

const sourceHostPort = env('MIGRATION_PROOF_SOURCE_HOST_PORT', '8081');
const targetHostPort = env('MIGRATION_PROOF_TARGET_HOST_PORT', '8080');

export const config = {
    sourceHostPort,
    targetHostPort,
    sourceUrl: env('MIGRATION_PROOF_SOURCE_URL', `http://127.0.0.1:${sourceHostPort}`),
    sourceDockerUrl: env('MIGRATION_PROOF_SOURCE_DOCKER_URL', 'http://source'),
    targetUrl: env('MIGRATION_PROOF_TARGET_URL', `http://127.0.0.1:${targetHostPort}`),
    sourceContainerName: env('MIGRATION_PROOF_SOURCE_CONTAINER', 'migration-proof-source'),
    targetContainerName: env('MIGRATION_PROOF_TARGET_CONTAINER', 'migration-proof-target'),
    sourceDatabaseName: env('MIGRATION_PROOF_SOURCE_DB_NAME', 'shopware'),
    targetAdminUsername: env('MIGRATION_PROOF_TARGET_ADMIN_USERNAME', 'admin'),
    targetAdminPassword: env('MIGRATION_PROOF_TARGET_ADMIN_PASSWORD', 'shopware'),
    targetPluginName: env('MIGRATION_PROOF_TARGET_PLUGIN_NAME', 'SwagMigrationAssistant'),
    sourceShopwareVersion: env('SOURCE_SHOPWARE_VERSION'),
    sourcePhpVersion: env('SOURCE_PHP_VERSION'),
    sourceNodeVersion: env('SOURCE_NODE_VERSION'),
    targetShopwareVersion: env('TARGET_SHOPWARE_VERSION'),
    targetPhpVersion: env('TARGET_PHP_VERSION'),
    targetNodeVersion: env('TARGET_NODE_VERSION'),
    outputDir: env('MIGRATION_PROOF_OUTPUT_DIR', resolve(import.meta.dir, '../output')),
    bootstrap: isEnabled(env('MIGRATION_PROOF_BOOTSTRAP', '')),
    debug: isEnabled(env('MIGRATION_PROOF_DEBUG', '')),

    sourceApiUser: env('MIGRATION_PROOF_SOURCE_API_USERNAME', 'migration-proof-api'),
    sourceApiPassword: env('MIGRATION_PROOF_SOURCE_API_PASSWORD', 'migration-proof-api-password'),
    sourceApiKey: env('MIGRATION_PROOF_SOURCE_API_KEY', 'migration-proof-api-key-1234567890abcdef'),

    connection: {
        name: env('MIGRATION_PROOF_SOURCE_CONNECTION_NAME', 'migration-proof-shopware-api'),
        profileName: env('MIGRATION_PROOF_SOURCE_PROFILE_NAME'),
        gatewayName: env('MIGRATION_PROOF_SOURCE_GATEWAY_NAME'),
    },

    polling: {
        readinessAttempts: 8,
        readinessIntervalMs: 2000,
        sourceReadyAttempts: 36,
        sourceReadyIntervalMs: 4000,
        migrationAttempts: 120,
        migrationIntervalMs: 4000,
        messengerStartupWaitMs: 750,
    },

    requestTimeoutMs: 20_000,

    messenger: {
        logPath: '/tmp/migration-proof-messenger.log',
        timeLimitSeconds: 1800,
        memoryLimit: '256M',
    },

    sourceReadyFlagPath: '/tmp/source-ready',

    docker: {
        stopTimeoutSeconds: 15,
    },

    step: {
        aborted: 'aborted',
        errorResolution: 'error-resolution',
        finished: 'finished',
        idle: 'idle',
        waitingForApprove: 'waiting-for-approve',
    },

    artifactPath: {
        sourceContainerLog: 'infrastructure/source-container.log',
        targetContainerLog: 'infrastructure/target-container.log',
        migrationStateHistory: 'migration/state-history.json',
        groupedMigrationLogs: 'migration/grouped-logs.json',
        rawMigrationLogs: 'migration/raw-logs.txt',
        migratedEntityCounts: 'verification/migrated-entity-counts.json',
    },

    apiPath: {
        version: '/api/_info/version',
        oauth: '/api/oauth/token',
        createConnection: '/api/_action/migration/create-new-connection',
        checkConnection: '/api/_action/migration/check-connection',
        generatePremapping: '/api/_action/migration/generate-premapping',
        writePremapping: '/api/_action/migration/write-premapping',
        startMigration: '/api/_action/migration/start-migration',
        getState: '/api/_action/migration/get-state',
        resumeAfterFixes: '/api/_action/migration/resume-after-fixes',
        approveFinished: '/api/_action/migration/approve-finished',
        downloadLogs: '/api/_action/migration/download-logs-of-run',
        getLogGroups: '/api/_action/migration/get-log-groups',
        getGeneralSetting: (id: string) => `/api/swag-migration-general-setting/${id}`,
        getDataSelections: (id: string) => `/api/_action/migration/data-selection?connectionId=${id}`,
        getGroupedLogs: (id: string) => `/api/_action/migration/get-grouped-logs-of-run?runUuid=${id}`,
        search: (name: string) => `/api/search/${name}`,
    },

    entityName: {
        generalSetting: 'swag_migration_general_setting',
        migrationRun: 'swag_migration_run',
    },

    auth: {
        clientId: 'administration',
        grantType: 'password',
    },
} as const;
