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
                if (newRunId.length > 0) {
                    this.fetchPremapping(newRunId);
                }
            }
        }
    },

    computed: {
        displayUnfilledPremapping() {
            return this.migrationUIStore.state.unfilledPremapping.length > 0;
        },

        displayFilledPremapping() {
            return this.migrationUIStore.state.filledPremapping.length > 0;
        }
    },

    methods: {
        fetchPremapping(runId) {
            this.isLoading = true;

            if (this.migrationUIStore.state.premapping !== null && this.migrationUIStore.state.premapping.length > 0) {
                this.migrationUIStore.setIsPremappingValid(false);
                this.$nextTick(() => {
                    this.validatePremapping();
                    this.instantValid = this.migrationUIStore.state.unfilledPremapping.length === 0;
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
                    this.migrationUIStore.setPremapping(premapping);
                    this.validatePremapping();
                    this.instantValid = this.migrationUIStore.state.unfilledPremapping.length === 0;

                    if (!this.instantValid) {
                        this.$emit('unfilledPremapping');
                    }

                    this.isLoading = false;
                }
            });
        },

        validatePremapping() {
            const isValid = !this.migrationUIStore.state.premapping.some((group) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid.length === 0;
                });
            });

            this.migrationUIStore.setIsPremappingValid(isValid);
        }
    }
});
