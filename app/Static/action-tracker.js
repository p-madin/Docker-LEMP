/**
 * action-tracker.js - Phase 12 UI Update
 * Handles UI status tracking for AJAX actions and IndexedDB dependency storage.
 */

class ActionTracker {
    constructor() {
        this.dbName = 'AppTestTracker';
        this.dbVersion = 1;
        this.storeName = 'dependencies';
        this.db = null;
        this.timeoutId = null;
        this.initUI();
        this.initDB();
    }

    initUI() {
        // Create styles for the spinner and progress bar
        if (!document.getElementById('action-tracker-styles')) {
            const style = document.createElement('style');
            style.id = 'action-tracker-styles';
            style.textContent = `
                @keyframes spinner {
                    to { transform: rotate(360deg); }
                }
                .at-spinner {
                    display: inline-block;
                    width: 14px;
                    height: 14px;
                    border: 2px solid rgba(255,255,255,0.3);
                    border-radius: 50%;
                    border-top-color: #fff;
                    animation: spinner 0.6s linear infinite;
                    margin-left: 8px;
                    vertical-align: middle;
                }
                #action-tracker-ui {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    width: 320px;
                    background: rgba(30, 30, 35, 0.9);
                    backdrop-filter: blur(10px);
                    color: #fff;
                    font-family: var(--font-family, Inter, sans-serif);
                    font-size: 13px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                    z-index: 9999;
                    overflow: hidden;
                    border: 1px solid rgba(255,255,255,0.1);
                }
                #action-tracker-ui summary {
                    padding: 10px 15px;
                    cursor: pointer;
                    font-weight: 600;
                    user-select: none;
                    background: rgba(255,255,255,0.05);
                    border-bottom: 1px solid rgba(255,255,255,0.1);
                    outline: none;
                }
                #action-tracker-ui summary::marker {
                    color: #888;
                }
                #action-history {
                    max-height: 150px;
                    overflow-y: auto;
                    padding: 10px;
                    margin: 0;
                    list-style: none;
                    border-bottom: 1px solid rgba(255,255,255,0.1);
                }
                #action-history li {
                    padding: 4px 0;
                    color: #ccc;
                    border-bottom: 1px dashed rgba(255,255,255,0.1);
                }
                #action-history li a {
                    color: #4da6ff;
                    text-decoration: none;
                }
                #current-action-block {
                    position: relative;
                    padding: 12px 15px;
                    background: rgba(40, 100, 200, 0.2);
                }
                #current-action-block.done {
                    background: rgba(40, 180, 100, 0.2);
                }
                #current-action-block.error {
                    background: rgba(200, 40, 40, 0.2);
                }
                #current-action-block a {
                    color: #4da6ff;
                    font-weight: bold;
                    text-decoration: none;
                    margin-left: 8px;
                }
                .at-progress-bar {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 3px;
                    background: #4da6ff;
                    width: 0%;
                    transition: width 3s linear;
                }
            `;
            document.head.appendChild(style);
        }

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
        const request = indexedDB.open(this.dbName, this.dbVersion);
        
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(this.storeName)) {
                const store = db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                store.createIndex('dependency', 'dependency', { unique: false });
                store.createIndex('url', 'url', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };

        request.onsuccess = (e) => {
            this.db = e.target.result;
            this.loadHistory();
        };

        request.onerror = (e) => {
            console.error('IndexedDB init error:', e);
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
        const date = new Date(record.timestamp).toLocaleTimeString();
        li.innerHTML = `[${date}] ID: ${record.dependency} - <a href="${record.url}" target="_blank">View</a>`;
        this.historyList.appendChild(li);
        this.historyList.scrollTop = this.historyList.scrollHeight;
    }

    setStatus(status, actionName, result = null) {
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

    appendDependency(dependencyId, url) {
        if (!this.db) {
            console.warn("IndexedDB not initialized yet.");
            return;
        }
        
        const record = {
            dependency: dependencyId,
            url: url,
            timestamp: Date.now()
        };

        const tx = this.db.transaction(this.storeName, 'readwrite');
        const store = tx.objectStore(this.storeName);
        const request = store.add(record);
        
        request.onsuccess = () => {
            this.addHistoryElement(record);
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.actionTracker = new ActionTracker();
});
