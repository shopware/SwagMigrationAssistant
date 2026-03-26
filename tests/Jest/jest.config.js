// For a detailed explanation regarding each configuration property, visit:
// https://jestjs.io/docs/en/configuration.html

// eslint-disable-next-line @typescript-eslint/no-require-imports
const { join, resolve } = require('path');

const artifactsPath = process.env.ARTIFACTS_PATH ? join(process.env.ARTIFACTS_PATH, '/build/artifacts/jest') : 'coverage';

// declare fallback for default setup
process.env.ADMIN_PATH = process.env.ADMIN_PATH || join(__dirname, '../../../../../src/Administration/Resources/app/administration');

module.exports = {
    preset: './tests/Jest/node_modules/@shopware-ag/jest-preset-sw6-admin/jest-preset.js',
    globals: {
        // optional, e.g. /www/sw6/platform/src/Administration/Resources/app/administration
        adminPath: process.env.ADMIN_PATH,
    },

    rootDir: '../../',

    moduleDirectories: [
        '<rootDir>/tests/Jest/node_modules',
        resolve(join(process.env.ADMIN_PATH, '/node_modules')),
    ],

    modulePathIgnorePatterns: [
        '<rootDir>/coverage/',
    ],

    testMatch: [
        '<rootDir>/tests/Jest/**/*.spec.js',
    ],

    collectCoverage: true,

    coverageDirectory: artifactsPath,

    coverageReporters: [
        'text',
        'cobertura',
        'html-spa',
    ],

    collectCoverageFrom: [
        '<rootDir>/src/Resources/app/administration/src/**/*.js',
        '<rootDir>/src/Resources/app/administration/src/**/*.ts',
    ],

    coverageProvider: 'v8',

    reporters: [
        'default',
        ['./tests/Jest/node_modules/jest-junit/index.js', {
            suiteName: 'SwagMigrationAssistant Administration Unit Tests',
            outputDirectory: join('../../', artifactsPath),
            outputName: 'junit.xml',
        }],
    ],

    setupFilesAfterEnv: [
        resolve(join(process.env.ADMIN_PATH, '/test/_setup/prepare_environment.js')),
    ],

    moduleNameMapper: {
        vue$: `${resolve(join(process.env.ADMIN_PATH, '/node_modules'))}/vue/dist/vue.cjs.js`,
        '@vue/(.*)$': `${resolve(join(process.env.ADMIN_PATH, '/node_modules'))}/@vue/$1`,
        'vue-i18n$': `${resolve(join(process.env.ADMIN_PATH, '/node_modules'))}/vue-i18n/dist/vue-i18n.cjs.js`,
        '^\@shopware-ag\/meteor-admin-sdk\/es\/(.*)': `${resolve(join(process.env.ADMIN_PATH, '/node_modules'))}/@shopware-ag/meteor-admin-sdk/umd/$1`,
        '^\@shopware-ag\/meteor-component-library$': `${resolve(join(process.env.ADMIN_PATH, '/node_modules'))}/@shopware-ag/meteor-component-library/dist/common/index.js`,
        '^@administration(.*)$': `${process.env.ADMIN_PATH}/src$1`,
        '^SwagMigrationAssistant/(.*)$': '<rootDir>/src/Resources/app/administration/src/$1',
        '^@/(.*)$': '<rootDir>/tests/Jest/src/$1',
        '^lodash-es$': 'lodash',
        '^lodash-es/(.*)$': 'lodash/$1',
    },

    transformIgnorePatterns: [
        '/node_modules/(?!(@shopware-ag/meteor-component-library|@shopware-ag/meteor-icon-kit|uuidv7|other)/)',
    ],

    // testEnvironment: 'jsdom',
    testEnvironmentOptions: {
        customExportConditions: ['node', 'node-addons'],
    },
};
