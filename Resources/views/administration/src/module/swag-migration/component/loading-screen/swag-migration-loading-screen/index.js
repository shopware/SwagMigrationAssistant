import { Component, State } from 'src/core/shopware';
import template from './swag-migration-loading-screen.html.twig';
import './swag-migration-loading-screen.less';
import { MIGRATION_DISPLAY_STATUS } from
    '../../../../../core/service/migration/swag-migration-worker-status-manager.service';

Component.register('swag-migration-loading-screen', {
    template,

    props: {
        profileName: {
            type: String
        }
    },

    data() {
        return {
            /** @type MigrationProcessStore */
            migrationProcessStore: State.getStore('migrationProcess')
        };
    },

    computed: {
        displayEntityGroups() {
            return this.migrationProcessStore.getDisplayEntityGroups();
        },

        progressBarCount() {
            return this.displayEntityGroups.length;
        },

        progressBarContainerGridStyle() {
            let style = '';
            for (let i = 0; i < this.progressBarCount; i += 1) {
                style = `${style} 1fr`;
            }

            return style;
        },

        currentStatus() {
            return MIGRATION_DISPLAY_STATUS[this.migrationProcessStore.state.statusIndex];
        },

        statusCount() {
            let statusCount = Object.keys(MIGRATION_DISPLAY_STATUS).length;
            let processMediaFiles = false;
            this.displayEntityGroups.forEach((group) => {
                if (group.processMediaFiles) {
                    processMediaFiles = true;
                }
            });

            if (!processMediaFiles) {
                statusCount = Object.values(MIGRATION_DISPLAY_STATUS).filter((status) => {
                    return (status !== 'PROCESS_MEDIA_FILES');
                }).length;
            }

            return statusCount;
        },

        statusShort() {
            return `${this.$t('swag-migration.index.loadingScreenCard.cardTitle', {
                step: this.migrationProcessStore.state.statusIndex + 1,
                total: this.statusCount
            })} - ${this.$t(`swag-migration.index.loadingScreenCard.status.${this.currentStatus}.short`)}`;
        },

        statusLong() {
            return this.$t(
                `swag-migration.index.loadingScreenCard.status.${this.currentStatus}.long`,
                { profileName: this.profileName }
            );
        },

        title() {
            return this.$t('swag-migration.index.loadingScreenCard.cardTitle', {
                step: this.migrationProcessStore.state.statusIndex + 1,
                total: this.statusCount
            });
        }
    }
});
