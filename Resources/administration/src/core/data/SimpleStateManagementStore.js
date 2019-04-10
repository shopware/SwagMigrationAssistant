import { types, object } from 'src/core/service/util.service';

/**
 * This store can be used for simple state management. For more details read the following:
 * https://vuejs.org/v2/guide/state-management.html#Simple-State-Management-from-Scratch
 *
 * To activate the debugging start the watcher like below:
 * psh administration:watch --DEBUG="true"
 */
class SimpleStateManagementStore {
    /**
     * @param {number} debugDepth how long is the stack in development.
     */
    constructor(debugDepth = 5) {
        this._debugStateMutationCounter = 0;
        this._debugDepth = debugDepth;
        this.state = {};
    }

    // setters (mutations)
    // define setter methods here. before mutating the state call the method _checkDebugging first.

    // getters (to use as computed properties)
    // define getter methods here...

    /**
     * Store debugging functionality.
     * if there are objects / arrays in the args they must be serialized with object.deepCopyObject.
     *
     * @param {any} currentValue
     * @param {array<any>} serializedStoreMethodArgs
     * @protected
     */
    _checkDebugging(currentValue, ...serializedStoreMethodArgs) {
        if (process.env.NODE_ENV !== 'development') {
            return;
        }

        if (types.isObject(currentValue)) {
            currentValue = object.deepCopyObject(currentValue);
        }

        const errorStack = (new Error()).stack.toString().split('\n');

        if (errorStack[0] === 'Error') {
            errorStack.shift(); // Remove chrome 'Error' as first entry.
        }

        errorStack.shift(); // Remove this _checkDebugging method from stack

        // Build method name with parameters that was called on the store
        const method = errorStack.shift().match(/(at\s[A-z]*\.?)?([A-z]+)/)[2]; // Remove the store method from stack

        // Rebuild stack string (only show it with a deep of {this._debugDepth} calls)
        let stackString = '';
        for (let i = 0; i < errorStack.length && i < this._debugDepth; i += 1) {
            if (errorStack[i] !== '') {
                stackString += `-> ${errorStack[i]}\n`;
            }
        }
        if (errorStack.length > this._debugDepth && errorStack[this._debugDepth] !== '') {
            stackString += '...\n';
        }

        // Output debug information
        this._debugStateMutationCounter += 1;
        console.debug(
            '[store]',
            `${this.constructor.name}.${method}#${this._debugStateMutationCounter}`,
            serializedStoreMethodArgs,
            '\n',
            'currentValue:',
            currentValue,
            '\n',
            stackString,
            '\n'
        );
    }
}

export default SimpleStateManagementStore;
