import utils from 'src/core/service/util.service';

export default class StorageBroadcastService {
    constructor(cb = () => {}, adapter = localStorage) {
        this._clientId = utils.createId();
        this._messageKey = 'storage-broadcast';
        this.adapter = adapter;
        this.cb = cb;

        window.addEventListener('storage', this.messageReceived.bind(this));
    }

    getClientId() {
        return this._clientId;
    }

    setClientId(id) {
        this._clientId = id;
    }

    sendMessage(data) {
        if (!data.id || !data.id.length) {
            data.id = this._clientId;
        }

        data = JSON.stringify(data);

        this.adapter.setItem(this._messageKey, data);
    }

    messageReceived(event) {
        if (event.key !== 'storage-broadcast') {
            return;
        }

        const data = JSON.parse(this.adapter.getItem(this._messageKey));
        this.adapter.removeItem(this._messageKey);

        if(!data || data.id === this._clientId) {
            return false;
        }

        this.cb.call(null, data);
    }
}
