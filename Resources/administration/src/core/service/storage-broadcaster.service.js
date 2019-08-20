const utils = Shopware.Utils;

export default class StorageBroadcastService {
    constructor(receiveCallback = () => {}, channelKey) {
        this._clientId = utils.createId();
        this._channelKey = channelKey; // To identify the messages of all StorageBroadcastServices
        this._receiveCallback = receiveCallback;

        window.addEventListener('storage', this.messageReceived.bind(this));
    }

    getClientId() {
        return this._clientId;
    }

    setClientId(id) {
        this._clientId = id;
    }

    /**
     * Send a data packet to all other browser tabs that listens
     *
     * @param data
     */
    sendMessage(data) {
        if (!data.id || !data.id.length) {
            data.id = this._clientId;
        }

        data = JSON.stringify(data);

        localStorage.setItem(this._channelKey, data);
    }

    /**
     * Handles receive logic, so only data from other tabs trigger the callback
     *
     * @param event
     * @returns {boolean}
     */
    messageReceived(event) {
        if (event.key !== this._channelKey) {
            return false;
        }

        const data = JSON.parse(localStorage.getItem(this._channelKey));
        localStorage.removeItem(this._channelKey);

        if (!data || data.id === this._clientId) {
            return false;
        }

        this._receiveCallback.call(null, data);
        return true;
    }
}
