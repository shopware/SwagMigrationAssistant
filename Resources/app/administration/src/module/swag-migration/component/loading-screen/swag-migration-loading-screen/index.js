import template from './swag-migration-loading-screen.html.twig';
import './swag-migration-loading-screen.scss';
import { MIGRATION_DISPLAY_STATUS } from
    '../../../../../core/service/migration/swag-migration-worker-status-manager.service';

const { Component } = Shopware;

Component.register('swag-migration-loading-screen', {
    template,

    data() {
        return {
            migrationUIState: this.$store.state['swagMigration/ui'],
            migrationProcessState: this.$store.state['swagMigration/process']
        };
    },

    computed: {
        displayEntityGroups() {
            return this.$store.getters['swagMigration/process/displayEntityGroups'];
        },

        currentStatus() {
            return MIGRATION_DISPLAY_STATUS[this.migrationProcessState.statusIndex];
        },

        progressBarValue() {
            return this.displayEntityGroups.reduce((sum, group) => sum + group.currentCount, 0);
        },

        progressBarMaxValue() {
            return this.displayEntityGroups.reduce((sum, group) => sum + group.total, 0);
        },

        progressBarTitle() {
            if (this.migrationProcessState.currentEntityGroupId === '') {
                return '';
            }

            return `${this.$t(
                `swag-migration.index.selectDataCard.dataSelection.${this.migrationProcessState.currentEntityGroupId}`
            )}`;
        },

        progressBarLeftPointDescription() {
            return this.currentStatus === undefined ? '' :
                `${this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.short`)}`;
        },

        caption() {
            return this.currentStatus === undefined ? '' :
                this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.caption`);
        },

        statusLong() {
            return this.currentStatus === undefined ? '' :
                this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.long`);
        },

        hint() {
            return this.currentStatus === undefined ? '' :
                this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.hint`);
        }
    }
});
