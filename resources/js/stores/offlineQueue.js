/**
 * Offline Queue — IndexedDB-backed queue for submissions made while offline.
 * Automatically syncs when the browser comes back online.
 */

const DB_NAME = 'healthintel_offline_queue';
const STORE_NAME = 'submissions';
const DB_VERSION = 1;

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export const offlineQueue = {
    async add(item) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.add({ ...item, created_at: Date.now(), synced: false });
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    async getAll() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    async remove(id) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },

    async syncAll() {
        const items = await this.getAll();
        const unsynced = items.filter(i => !i.synced);
        for (const item of unsynced) {
            try {
                const response = await fetch(item.endpoint, {
                    method: item.method || 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + (item.token || '') },
                    body: item.payload,
                });
                if (response.ok) {
                    await this.remove(item.id);
                }
            } catch (e) {
                // Will retry on next sync
                console.warn('Offline sync failed for item', item.id, e);
            }
        }
    },

    async getCount() {
        const items = await this.getAll();
        return items.length;
    }
};

// Auto-sync when coming back online
if (typeof window !== 'undefined') {
    window.addEventListener('online', () => {
        offlineQueue.syncAll();
    });
}