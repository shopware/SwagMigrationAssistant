import { Component, State } from 'src/core/shopware';
import template from './swag-migration-loading-screen.html.twig';
import './swag-migration-loading-screen.scss';
import { MIGRATION_DISPLAY_STATUS } from
    '../../../../../core/service/migration/swag-migration-worker-status-manager.service';

Component.register('swag-migration-loading-screen', {
    template,

    data() {
        return {
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess'),
            /** @type MigrationUIStore */
            migrationUIStore: State.getStore('migrationUI')
        };
    },

    computed: {
        displayEntityGroups() {
            return this.migrationProcessStore.getDisplayEntityGroups();
        },

        currentStatus() {
            return MIGRATION_DISPLAY_STATUS[this.migrationProcessStore.state.statusIndex];
        },

        progressBarValue() {
            return this.displayEntityGroups.reduce((sum, group) => sum + group.currentCount, 0);
        },

        progressBarMaxValue() {
            return this.displayEntityGroups.reduce((sum, group) => sum + group.total, 0);
        },

        progressBarTitle() {
            if (this.migrationProcessStore.state.currentEntityGroupId === '') {
                return '';
            }

            return `${this.$t(
                `swag-migration.index.selectDataCard.dataSelection.${this.migrationProcessStore.state.currentEntityGroupId}`
            )}`;
        },

        progressBarLeftPointDescription() {
            return `${this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.short`)}`;
        },

        caption() {
            return this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.caption`);
        },

        statusLong() {
            return this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.long`);
        },

        hint() {
            return this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.hint`);
        }
    }
});
