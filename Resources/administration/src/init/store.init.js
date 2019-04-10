import { Application } from 'src/core/shopware';
import MigrationProcessStore from '../core/data/MigrationProcessStore';
import MigrationUIStore from '../core/data/MigrationUIStore';


const stateFactory = Application.getContainer('factory').state;
stateFactory.registerStore('migrationProcess', new MigrationProcessStore());
stateFactory.registerStore('migrationUI', new MigrationUIStore());
