import template from './swag-migration-loading-screen-takeover.html.twig';
import './swag-migration-loading-screen-takeover.scss';
import { MIGRATION_STATUS } from '../../../../../core/service/migration/swag-migration-worker-status-manager.service';

const { Component, State } = Shopware;

const TAKEOVER_STATE = Object.freeze({
    RUNNING: 'running',
    INTERRUPTED: 'interrupted',
    ABORTED: 'aborted',
});


Component.register('swag-migration-loading-screen-takeover', {
    template,

    inject: {
        /** @var {MigrationWorkerService} migrationWorkerService */
        migrationWorkerService: 'migrationWorkerService',
    },

    props: {
        isTakeoverForbidden: {
            type: Boolean,
        },
        isMigrationInterrupted: {
            type: Boolean,
        },
    },

    data() {
        return {
            isLoading: true,
            possibleState: TAKEOVER_STATE,
            state: TAKEOVER_STATE.RUNNING,
            showTakeoverModal: false,
            showAbortModal: false,
            showRedirectModal: false,
        };
    },

    computed: {
        titleSnippet() {
            if (this.isTakeoverForbidden) {
                return 'swag-migration.index.loadingScreenCard.takeoverScreen.forbidden.title';
            }

            return `swag-migration.index.loadingScreenCard.takeoverScreen.${this.state}.title`;
        },
        messageSnippet() {
            if (this.isTakeoverForbidden) {
                return 'swag-migration.index.loadingScreenCard.takeoverScreen.forbidden.message';
            }

            return `swag-migration.index.loadingScreenCard.takeoverScreen.${this.state}.message`;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.isMigrationInterrupted) {
                this.state = TAKEOVER_STATE.INTERRUPTED;
            } else {
                this.state = TAKEOVER_STATE.RUNNING;
            }

            this.isLoading = false;
        },

        refreshState() {
            this.isLoading = true;
            return this.migrationWorkerService.isMigrationRunningInOtherTab().then((isRunning) => {
                if (isRunning) {
                    this.isLoading = false;
                    return Promise.resolve();
                }

                return this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                    if (runState.isMigrationRunning === false) {
                        this.isLoading = false;
                        this.state = TAKEOVER_STATE.ABORTED;
                        return;
                    }

                    if (this.isMigrationInterrupted) {
                        this.state = TAKEOVER_STATE.INTERRUPTED;
                    } else {
                        this.state = TAKEOVER_STATE.RUNNING;
                    }

                    this.isLoading = false;
                }).catch(() => {
                    this.isLoading = false;
                });
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onCheckButtonClick() {
            this.isLoading = true;
            return this.migrationWorkerService.checkForRunningMigration().then((runState) => {
                if (runState.isMigrationRunning === false) {
                    this.isLoading = false;
                    this.showRedirectModal = true;
                    return;
                }

                if (runState.status === MIGRATION_STATUS.PREMAPPING) {
                    this.isLoading = false;
                    this.showAbortModal = true;
                    return;
                }

                this.isLoading = false;
                this.showTakeoverModal = true;
            });
        },

        onCloseTakeoverModal() {
            this.showTakeoverModal = false;
        },

        onTakeover() {
            this.showTakeoverModal = false;
            this.$nextTick(() => {
                // this will remove this component from the DOM so it must be called after the modal ist closed.
                this.$emit('onTakeoverMigration');
            });
        },

        onCloseAbortModal() {
            this.showAbortModal = false;
        },

        onAbort() {
            this.showAbortModal = false;
            this.$nextTick(() => {
                // this will remove this component from the DOM so it must be called after the modal ist closed.
                this.$emit('onAbortMigration');
            });
        },

        onCloseRedirectModal() {
            this.showRedirectModal = false;
        },

        onRedirect() {
            this.showRedirectModal = false;
            this.$nextTick(() => {
                State.commit('swagMigration/process/setIsMigrating', false);
                State.commit('swagMigration/ui/setIsLoading', false);
                this.$router.push({ name: 'swag.migration.index.main' });
            });
        },
    },
});
