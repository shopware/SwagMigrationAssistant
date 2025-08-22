import template from './swag-migration-wizard-page-credentials.html.twig';
import type { MigrationCredentials } from '../../../../../type/types';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    emits: [
        'onCredentialsChanged',
        'onChildRouteReadyChanged',
        'onTriggerPrimaryClick',
    ],

    props: {
        credentialsComponent: {
            type: String,
            default: '',
        },

        credentials: {
            type: Object as PropType<MigrationCredentials>,
            default() {
                return {};
            },
        },
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        componentIsLoaded() {
            return Shopware.Component.getComponentRegistry().has(this.credentialsComponent);
        },
    },
});
