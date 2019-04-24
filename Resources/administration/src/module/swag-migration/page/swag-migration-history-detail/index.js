import { Component, State } from 'src/core/shopware';
import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './swag-migration-history-detail.html.twig';
import './swag-migration-history-detail.scss';

Component.register('swag-migration-history-detail', {
    template,

    data() {
        return {
            runId: '',
            migrationRun: {},
            showModal: true,
            isLoading: true
        };
    },

    computed: {
        migrationRunStore() {
            return State.getStore('swag_migration_run');
        }
    },

    created() {
        if (!this.$route.params.id) {
            this.isLoading = false;
            this.onCloseModal();
            return;
        }

        this.runId = this.$route.params.id;
        const params = {
            limit: 1,
            criteria: CriteriaFactory.equals('id', this.runId)
        };

        this.migrationRunStore.getList(params).then((response) => {
            if (!response ||
                (response && response.items.length < 1)) {
                this.isLoading = false;
                this.onCloseModal();
                return;
            }

            this.migrationRun = response.items[0];
            this.isLoading = false;
        }).catch(() => {
            this.isLoading = false;
            this.onCloseModal();
        });
    },

    methods: {
        onCloseModal() {
            this.showModal = false;
            this.$nextTick(() => {
                this.$router.go(-1);
            });
        }
    }
});
