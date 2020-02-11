import template from './swag-migration-premapping.html.twig';
import { UI_COMPONENT_INDEX } from '../../../../../core/data/migrationUI.store';
import './swag-migration-premapping.scss';

const { Component } = Shopware;

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
            migrationUIState: this.$store.state['swagMigration/ui']
        };
    },

    created() {
        this.$store.commit('swagMigration/ui/setIsPremappingValid', false);
    },

    watch: {
        runId: {
            immediate: true,
            handler(newRunId) {
                if (newRunId.length > 0) {
                    this.fetchPremapping(newRunId);
                }
            }
        }
    },

    computed: {
        runId() {
            return this.$store.state['swagMigration/process'].runId;
        },

        displayUnfilledPremapping() {
            return this.migrationUIState.unfilledPremapping.length > 0;
        },

        displayFilledPremapping() {
            return this.migrationUIState.filledPremapping.length > 0;
        },

        premappingValid() {
            return this.migrationUIState.isPremappingValid;
        }
    },

    methods: {
        fetchPremapping(runId) {
            this.isLoading = true;

            if (this.migrationUIState.premapping !== null && this.migrationUIState.premapping.length > 0) {
                this.$nextTick(() => {
                    this.notifyPremappingValidWatchers(
                        this.validatePremapping(false)
                    );
                    this.isLoading = false;
                });
                return;
            }

            this.migrationService.generatePremapping(runId).then((premapping) => {
                if (premapping.length === 0) {
                    this.$store.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
                    this.migrationWorkerService.startMigration(this.runId).catch(() => {
                        this.onInvalidMigrationAccessToken();
                    });
                } else {
                    this.$store.commit('swagMigration/ui/setPremapping', premapping);
                    this.notifyPremappingValidWatchers(
                        this.validatePremapping(false)
                    );

                    this.isLoading = false;
                }
            });
        },

        notifyPremappingValidWatchers(isValid) {
            if (isValid !== this.migrationUIState.isPremappingValid) {
                this.$store.commit('swagMigration/ui/setIsPremappingValid', isValid);
                return;
            }

            // It is needed to trigger a watcher event here, even if the value does not have been changed.
            this.$store.commit('swagMigration/ui/setIsPremappingValid', !isValid);
            this.$nextTick(() => {
                this.$store.commit('swagMigration/ui/setIsPremappingValid', isValid);
            });
        },

        validatePremapping(updateStore = true) {
            const isValid = !this.migrationUIState.premapping.some((group) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid.length === 0;
                });
            });

            if (updateStore) {
                this.$store.commit('swagMigration/ui/setIsPremappingValid', isValid);
            }

            return isValid;
        },

        getErrorCountForGroupTab(group) {
            return group.mapping.reduce((currentValue, mapping) => {
                if (mapping.destinationUuid === null || mapping.destinationUuid.length === 0) {
                    return currentValue + 1;
                }

                return currentValue;
            }, 0);
        }
    }
});
