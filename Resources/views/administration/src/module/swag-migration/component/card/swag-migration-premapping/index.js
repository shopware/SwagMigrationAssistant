import { Component, State } from 'src/core/shopware';
import template from './swag-migration-premapping.html.twig';
import { UI_COMPONENT_INDEX } from '../../../../../core/data/MigrationUIStore';
import './swag-migration-premapping.scss';

Component.register('swag-migration-premapping', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService',
        /** @var {MigrationWorkerService} migrationWorkerService */
        migrationWorkerService: 'migrationWorkerService'
    },

    data() {
        return {
            isLoading: false,
            premappingInput: [],
            instantValid: false,
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI')
        };
    },

    created() {
        this.migrationUIStore.setIsPremappingValid(false);
    },

    watch: {
        'migrationProcessStore.state.runId': {
            immediate: true,
            handler(newRunId) {
                this.fetchPremapping(newRunId);
            }
        }
    },

    methods: {
        fetchPremapping(runId) {
            this.isLoading = true;

            if (this.migrationUIStore.state.premapping !== null && this.migrationUIStore.state.premapping.length > 0) {
                this.migrationUIStore.setIsPremappingValid(false);
                this.$nextTick(() => {
                    this.premappingInput = this.migrationUIStore.state.premapping;
                    this.validatePremapping();
                    this.instantValid = this.migrationUIStore.state.isPremappingValid;
                    this.isLoading = false;
                });
                return;
            }

            this.migrationService.generatePremapping(runId).then((premapping) => {
                if (premapping.length === 0) {
                    this.migrationUIStore.setComponentIndex(UI_COMPONENT_INDEX.LOADING_SCREEN);
                    this.migrationWorkerService.startMigration(
                        this.migrationProcessStore.state.runId
                    ).catch(() => {
                        this.onInvalidMigrationAccessToken();
                    });
                } else {
                    this.premappingInput = premapping;
                    this.validatePremapping();
                    this.instantValid = this.migrationUIStore.state.isPremappingValid;
                    this.isLoading = false;
                }
            });
        },

        validatePremapping() {
            let isValid = true;
            this.premappingInput.forEach((group) => {
                group.mapping.forEach((mapping) => {
                    if (mapping.destinationUuid === null || mapping.destinationUuid.length === 0) {
                        isValid = false;
                    }
                });
            });

            this.migrationUIStore.setPremapping(this.premappingInput);
            this.migrationUIStore.setIsPremappingValid(isValid);
        }
    }
});
