import MigrationProcessStore from '../core/data/MigrationProcessStore';
import MigrationUIStore from '../core/data/MigrationUIStore';

const { Application } = Shopware;
const stateFactory = Application.getContainer('factory').stateDeprecated;
stateFactory.registerStore('migrationProcess', new MigrationProcessStore());
stateFactory.registerStore('migrationUI', new MigrationUIStore());
