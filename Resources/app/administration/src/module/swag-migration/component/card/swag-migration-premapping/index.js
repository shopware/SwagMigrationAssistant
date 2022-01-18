import template from './swag-migration-premapping.html.twig';
import { UI_COMPONENT_INDEX } from '../../../../../core/data/migrationUI.store';
import './swag-migration-premapping.scss';

const { Component, State } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-migration-premapping', {
    template,

    inject: {
        /** @var {MigrationApiService} migrationService */
        migrationService: 'migrationService',
        /** @var {MigrationWorkerService} migrationWorkerService */
        migrationWorkerService: 'migrationWorkerService',
    },

    data() {
        return {
            isLoading: false,
            premappingInput: [],
        };
    },

    computed: {
        ...mapState('swagMigration/process', [
            'runId',
        ]),

        ...mapState('swagMigration/ui', [
            'unfilledPremapping',
            'filledPremapping',
            'isPremappingValid',
            'premapping',
        ]),

        displayUnfilledPremapping() {
            return this.unfilledPremapping.length > 0;
        },

        displayFilledPremapping() {
            return this.filledPremapping.length > 0;
        },
    },

    watch: {
        runId: {
            immediate: true,
            handler(newRunId) {
                if (newRunId.length < 1) {
                    return;
                }

                this.fetchPremapping(newRunId);
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            State.commit('swagMigration/ui/setIsPremappingValid', false);
        },

        fetchPremapping(runId) {
            this.isLoading = true;

            if (this.premapping !== null && this.premapping.length > 0) {
                this.$nextTick(() => {
                    this.notifyPremappingValidWatchers(
                        this.validatePremapping(false),
                    );
                    this.isLoading = false;
                });
                return Promise.resolve();
            }

            return this.migrationService.generatePremapping(runId).then((premapping) => {
                if (premapping.length === 0) {
                    State.commit('swagMigration/ui/setComponentIndex', UI_COMPONENT_INDEX.LOADING_SCREEN);
                    this.migrationWorkerService.startMigration(this.runId).catch(() => {
                        this.onInvalidMigrationAccessToken();
                    });
                } else {
                    State.commit('swagMigration/ui/setPremapping', premapping);
                    this.notifyPremappingValidWatchers(
                        this.validatePremapping(false),
                    );

                    this.isLoading = false;
                }
            });
        },

        notifyPremappingValidWatchers(isValid) {
            if (isValid !== this.isPremappingValid) {
                State.commit('swagMigration/ui/setIsPremappingValid', isValid);
                return;
            }

            // It is needed to trigger a watcher event here, even if the value does not have been changed.
            State.commit('swagMigration/ui/setIsPremappingValid', !isValid);
            this.$nextTick(() => {
                State.commit('swagMigration/ui/setIsPremappingValid', isValid);
            });
        },

        validatePremapping(updateStore = true) {
            const isValid = !this.premapping.some((group) => {
                return group.mapping.some((mapping) => {
                    return mapping.destinationUuid === null || mapping.destinationUuid.length === 0;
                });
            });

            if (updateStore) {
                State.commit('swagMigration/ui/setIsPremappingValid', isValid);
            }

            return isValid;
        },
    },
});
