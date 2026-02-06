import type { Property } from '@administration/src/core/data/entity-definition.data';
import template from './swag-migration-error-resolution-field.html.twig';
import './swag-migration-error-resolution-field.scss';
import type { ErrorResolutionTableData } from '../../swag-migration-error-resolution-step';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../../service/swag-migration-error-resolution.service';
import { MIGRATION_API_SERVICE } from '../../../../../../core/service/api/swag-migration.api.service';

/**
 * @private
 */
export interface SwagMigrationErrorResolutionFieldData {
    exampleValue: string | null;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_ERROR_RESOLUTION_SERVICE,
        MIGRATION_API_SERVICE,
    ],

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        log: {
            type: Object as PropType<ErrorResolutionTableData>,
            required: true,
        },
        error: {
            type: Object as PropType<{ detail: string }>,
            required: false,
            default: null,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data(): SwagMigrationErrorResolutionFieldData {
        return {
            exampleValue: null,
        };
    },

    async created() {
        await this.fetchExampleValue();
    },

    computed: {
        isUnhandledField(): boolean {
            return this.swagMigrationErrorResolutionService.isUnhandledField(this.log.entityName, this.log.fieldName);
        },

        entityField(): Property | null {
            return this.swagMigrationErrorResolutionService.getEntityField(this.log.entityName, this.log.fieldName);
        },

        isScalarField(): boolean {
            return this.swagMigrationErrorResolutionService.isScalarField(this.log.entityName, this.log.fieldName);
        },

        effectiveEntityField(): Property | null {
            return this.swagMigrationErrorResolutionService.getEffectiveEntityField(this.log.entityName, this.log.fieldName);
        },

        fieldType(): string | null {
            return this.swagMigrationErrorResolutionService.getFieldType(this.log.entityName, this.log.fieldName);
        },

        shouldFetchExample(): boolean {
            return this.isUnhandledField || this.fieldType === 'editor';
        },
    },

    methods: {
        async fetchExampleValue(): Promise<void> {
            if (!this.shouldFetchExample) {
                return;
            }

            try {
                const response = await this.migrationApiService.getExampleFieldStructure(
                    this.log.entityName,
                    this.log.fieldName,
                );

                this.exampleValue = response?.example ?? null;
            } catch {
                this.createNotificationError({
                    message: this.$tc('swag-migration.index.error-resolution.errors.fetchExampleFailed'),
                });
            }
        },
    },
});
