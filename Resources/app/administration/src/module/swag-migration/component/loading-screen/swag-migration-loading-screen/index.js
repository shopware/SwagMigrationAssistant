import template from './swag-migration-loading-screen.html.twig';
import './swag-migration-loading-screen.scss';
import { MIGRATION_DISPLAY_STATUS } from
    '../../../../../core/service/migration/swag-migration-worker-status-manager.service';

const { Component, State } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('swag-migration-loading-screen', {
    template,

    computed: {
        ...mapState('swagMigration/process', [
            'statusIndex',
            'currentEntityGroupId',
        ]),

        ...mapState('swagMigration/ui', [
            'isPaused',
        ]),

        ...mapGetters('swagMigration/process', [
            'displayEntityGroups',
        ]),

        currentStatus() {
            return MIGRATION_DISPLAY_STATUS[this.statusIndex];
        },

        progressBarValue() {
            return this.displayEntityGroups.reduce((sum, group) => sum + group.currentCount, 0);
        },

        progressBarMaxValue() {
            return this.displayEntityGroups.reduce((sum, group) => sum + group.total, 0);
        },

        progressBarTitle() {
            if (this.currentEntityGroupId === '') {
                return '';
            }

            return `${this.$tc(
                `swag-migration.index.selectDataCard.dataSelection.${this.currentEntityGroupId}`,
            )}`;
        },

        progressBarLeftPointDescription() {
            return this.currentStatus === undefined ? '' :
                `${this.$tc(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.short`)}`;
        },

        caption() {
            return this.currentStatus === undefined ? '' :
                this.$tc(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.caption`);
        },

        statusLong() {
            return this.currentStatus === undefined ? '' :
                this.$tc(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.long`);
        },

        hint() {
            return this.currentStatus === undefined ? '' :
                this.$tc(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.hint`);
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            State.commit('swagMigration/process/resetProgress');
        },
    },
});
