/**
 * action-tracker.js - Phase 12 UI Update
 * Handles UI status tracking for AJAX actions and IndexedDB dependency storage.
 */

class ActionTracker {
    constructor() {
        this.dbName = 'AppTestTracker';
        this.dbVersion = 3;
        this.storeName = 'dependencies';
        this.db = null;
        this.timeoutId = null;
        
        const userMeta = document.querySelector('meta[name="session-user-id"]');
        this.isLoggedIn = userMeta && userMeta.content !== '';

        this.initDB();
        
        if (this.isLoggedIn) {
            this.initUI();
        }
    }

    initUI() {
        this.container = document.createElement('details');
        this.container.id = 'action-tracker-ui';
        
        const summary = document.createElement('summary');
        summary.textContent = 'Action Tracker';
        this.container.appendChild(summary);

        this.historyList = document.createElement('ul');
        this.historyList.id = 'action-history';
        this.container.appendChild(this.historyList);

        this.currentBlock = document.createElement('div');
        this.currentBlock.id = 'current-action-block';
        this.currentBlock.style.display = 'none';
        
        this.statusText = document.createElement('span');
        this.currentBlock.appendChild(this.statusText);

        this.progressBar = document.createElement('div');
        this.progressBar.className = 'at-progress-bar';
        this.currentBlock.appendChild(this.progressBar);

        this.container.appendChild(this.currentBlock);
        document.body.appendChild(this.container);

        if (localStorage.getItem('actionTrackerOpen') === 'true') {
            this.container.open = true;
        }

        this.container.addEventListener('toggle', () => {
            localStorage.setItem('actionTrackerOpen', this.container.open);
        });

        // Sync state across multiple tabs natively without polling
        window.addEventListener('storage', (e) => {
            if (e.key === 'actionTrackerOpen') {
                this.container.open = (e.newValue === 'true');
            }
        });

        this.dialog = document.createElement('dialog');
        this.dialog.id = 'at-inspect-dialog';
        this.dialog.innerHTML = `
            <button class="at-inspect-close" id="at-inspect-close-btn">✖</button>
            <h3 style="margin-top:0">Inspect Payload & Response</h3>
            <pre id="at-inspect-content" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
        `;
        document.body.appendChild(this.dialog);

        document.getElementById('at-inspect-close-btn').addEventListener('click', () => {
            this.dialog.close();
        });

        // Cancel timeout on hover or click
        const cancelTimeout = () => {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
                this.timeoutId = null;
                // Freeze progress bar
                const currentWidth = window.getComputedStyle(this.progressBar).width;
                this.progressBar.style.transition = 'none';
                this.progressBar.style.width = currentWidth;
                this.progressBar.style.background = '#888'; // Visual indication of cancelled redirect
            }
        };

        this.currentBlock.addEventListener('mouseenter', cancelTimeout);
        this.currentBlock.addEventListener('click', cancelTimeout);
    }

    initDB() {
        this.dbVersion = 3; // Upgrade version to drop legacy schema
        const request = indexedDB.open(this.dbName, this.dbVersion);
        
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (db.objectStoreNames.contains(this.storeName)) {
                db.deleteObjectStore(this.storeName);
            }
            const store = db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
            store.createIndex('dependency', 'dependency', { unique: false });
            store.createIndex('url', 'url', { unique: false });
            store.createIndex('timestamp', 'timestamp', { unique: false });
        };

        request.onsuccess = (e) => {
            this.db = e.target.result;
            
            this.db.onversionchange = () => {
                this.db.close();
                this.db = null;
                console.warn("Database is upgrading in another tab. Connection closed.");
            };
            
            const userMeta = document.querySelector('meta[name="session-user-id"]');
            const currentUserId = userMeta ? userMeta.content : '';
            const lastUserId = localStorage.getItem('at_last_user_id');
            
            if (lastUserId !== null && currentUserId !== lastUserId) {
                // Session changed (login, logout, or rotated)! Wipe the history.
                this.clearHistory().then(() => {
                    localStorage.setItem('at_last_user_id', currentUserId);
                    if (this.isLoggedIn) this.loadHistory();
                });
            } else {
                localStorage.setItem('at_last_user_id', currentUserId);
                if (this.isLoggedIn) this.loadHistory();
            }
        };

        request.onerror = (e) => {
            console.error('IndexedDB init error:', e);
        };
        
        request.onblocked = (e) => {
            console.warn('IndexedDB upgrade blocked. Please close other tabs of this application.');
        };
    }

    loadHistory() {
        if (!this.db) return;
        const tx = this.db.transaction(this.storeName, 'readonly');
        const store = tx.objectStore(this.storeName);
        const request = store.getAll();
        
        request.onsuccess = (e) => {
            const records = e.target.result;
            this.historyList.innerHTML = '';
            // Load last 20 records
            records.slice(-20).forEach(record => {
                this.addHistoryElement(record);
            });
        };
    }

    addHistoryElement(record) {
        const li = document.createElement('li');
        const dateStr = new Date(record.timestamp).toLocaleString();
        
        let urlLink = record.url ? `<a href="${record.url}" target="_blank">GoTo</a>` : '';
        let inspectLink = `<a href="#" class="at-inspect-link">Inspect</a>`;
        
        li.innerHTML = `[${dateStr}] ${record.method}: ${record.path} ${record.status} ${urlLink} | ${inspectLink}`;
        
        const linkElem = li.querySelector('.at-inspect-link');
        if (linkElem) {
            linkElem.addEventListener('click', (e) => {
                e.preventDefault();
                this.inspectRecord(record.id);
            });
        }
        
        this.historyList.appendChild(li);
        this.historyList.scrollTop = this.historyList.scrollHeight;
    }

    inspectRecord(id) {
        if (!this.db) return;
        const tx = this.db.transaction(this.storeName, 'readonly');
        const store = tx.objectStore(this.storeName);
        const req = store.get(id);
        req.onsuccess = (e) => {
            const rec = e.target.result;
            if (rec) {
                const content = {
                    Payload: rec.payload,
                    Response: rec.response
                };
                document.getElementById('at-inspect-content').textContent = JSON.stringify(content, null, 2);
                this.dialog.showModal();
            }
        };
    }
    
    clearHistory() {
        return new Promise((resolve) => {
            if (!this.db) return resolve();
            const tx = this.db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            store.clear();
            tx.oncomplete = () => {
                if (this.historyList) {
                    this.historyList.innerHTML = '';
                }
                resolve();
            };
            tx.onerror = () => resolve();
        });
    }

    setStatus(status, actionName, result = null) {
        if (!this.isLoggedIn) return;
        this.container.open = true;
        this.currentBlock.style.display = 'block';
        this.currentBlock.className = '';
        
        // Reset progress bar
        this.progressBar.style.transition = 'none';
        this.progressBar.style.width = '0%';
        this.progressBar.style.background = '#4da6ff';

        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }

        if (status === 'in_progress') {
            this.currentBlock.classList.add('in-progress');
            this.statusText.innerHTML = `POST: ${actionName} <span class="at-spinner"></span>`;
        } else if (status === 'done') {
            this.currentBlock.classList.add('done');
            let targetUrl = result && (result.redirect || result.url) ? (result.redirect || result.url) : '#';
            this.statusText.innerHTML = `POST: ${actionName} complete <a href="${targetUrl}">Goto</a>`;
            
            // Start progress bar animation
            setTimeout(() => {
                this.progressBar.style.transition = 'width 3s linear';
                this.progressBar.style.width = '100%';
            }, 50);

            if (targetUrl !== '#') {
                this.timeoutId = setTimeout(() => {
                    window.location.href = targetUrl;
                }, 3000);
            }
        } else if (status === 'error') {
            this.currentBlock.classList.add('error');
            this.statusText.innerHTML = `POST: ${actionName} failed`;
        }
    }

    appendHistory(method, path, status, url, payload, responseData) {
        if (!this.db) {
            console.warn("IndexedDB not initialized yet.");
            return;
        }
        
        const record = {
            method: method,
            path: path,
            status: status,
            url: url,
            payload: payload,
            response: responseData,
            timestamp: Date.now()
        };

        const tx = this.db.transaction(this.storeName, 'readwrite');
        const store = tx.objectStore(this.storeName);
        const request = store.add(record);
        
        request.onsuccess = (e) => {
            record.id = e.target.result; // auto-incremented key
            this.addHistoryElement(record);
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.actionTracker = new ActionTracker();
});
