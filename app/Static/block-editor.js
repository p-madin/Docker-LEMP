/**
 * Phase 1: Vanilla JS Block Editor
 * Handles Drag & Drop, Canvas manipulation, Properties editing, and Inline editing.
 */
class BlockEditor {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.pageId = window.EDITOR_STATE?.pageId || 0;
        this.csrfToken = window.EDITOR_STATE?.csrfToken || '';
        this.pageTitle = window.EDITOR_STATE?.pageData?.pagTitle || 'Untitled Page';
        this.pageSlug = window.EDITOR_STATE?.pageData?.pagSlug || '';
        this.activeBlock = null;
        this.toolbar = null;
        this.draggedBlockType = null;
        this.blockCounter = 0;
        this.isSaving = false;
        this.forms = [];

        this.initUI();
        this.bindEvents();
        this.hydrate();
        this.fetchForms();
        this.fetchDataProviders();
        this.selectBlock(null);
    }

    async fetchDataProviders() {
        try {
            const formData = new FormData();
            formData.append('csrf_token', this.csrfToken);
            const res = await fetch('/dataProviders', { method: "POST", body: formData });
            const result = await res.json();
            if (result.success) {
                const select = document.getElementById('prop-data-provider');
                result.providers.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.innerText = p.name;
                    select.appendChild(opt);
                });
            }
        } catch (e) {
            console.error('Failed to fetch data providers', e);
        }
    }

    hydrate() {
        if (window.EDITOR_STATE?.elements) {
            const elements = window.EDITOR_STATE.elements;
            const elementsMap = {};

            // Pass 1: Create all blocks
            elements.forEach(elData => {
                const el = this.addBlock(elData.eleType, elData);
                if (elData.elePK) {
                    elementsMap[elData.elePK] = el;
                }
            });

            // Pass 2: Re-parent nested blocks
            elements.forEach(elData => {
                if (elData.eleParentFK && elData.eleParentFK != 0) {
                    const child = elementsMap[elData.elePK];
                    const parent = elementsMap[elData.eleParentFK];
                    if (child && parent) {
                        parent.appendChild(child);
                    }
                }
            });
        }
    }

    initUI() {
        // Create the main container (3-pane layout)
        this.editorContainer = document.createElement('div');
        this.editorContainer.className = 'block-editor-container';

        // 1. Left Sidebar (Palette)
        this.palette = document.createElement('div');
        this.palette.className = 'editor-sidebar left';

        const paletteHeader = document.createElement('div');
        paletteHeader.className = 'sidebar-header';
        paletteHeader.innerText = 'Components';

        const paletteContent = document.createElement('div');
        paletteContent.className = 'sidebar-content';

        const blockTypes = [
            { type: 'heading', label: 'Heading (H2)' },
            { type: 'paragraph', label: 'Paragraph' },
            { type: 'container', label: 'Container' },
            { type: 'form', label: 'Form' },
            { type: 'table', label: 'Data Table' },
            { type: 'chart', label: 'Data Chart' },
            { type: 'button', label: 'Button' },
            { type: 'divider', label: 'Divider' }
        ];

        blockTypes.forEach(bt => {
            const el = document.createElement('div');
            el.className = 'palette-item';
            el.draggable = true;
            el.dataset.type = bt.type;
            el.innerText = bt.label;

            el.addEventListener('dragstart', (e) => {
                this.draggedBlockType = bt.type;
                e.dataTransfer.setData('text/plain', bt.type);
            });
            paletteContent.appendChild(el);
        });

        this.palette.appendChild(paletteHeader);
        this.palette.appendChild(paletteContent);

        // 2. Center Canvas
        this.canvasWrapper = document.createElement('div');
        this.canvasWrapper.className = 'editor-canvas-wrapper';

        this.canvasContent = document.createElement('div');
        this.canvasContent.className = 'canvas-content';

        this.canvasWrapper.appendChild(this.canvasContent);

        // 3. Right Sidebar (Properties)
        this.propertiesPanel = document.createElement('div');
        this.propertiesPanel.className = 'editor-sidebar right';

        const propHeader = document.createElement('div');
        propHeader.className = 'sidebar-header';
        propHeader.innerText = 'Properties';

        const propContent = document.createElement('div');
        propContent.className = 'sidebar-content';
        propContent.innerHTML = `
            <div id="prop-container" style="display:none;">
                <div class="sidebar-section-title">Block Properties</div>
                <div class="prop-group">
                    <label>Block Type</label>
                    <input type="text" id="prop-type" class="prop-input" disabled />
                </div>
                <div class="prop-group">
                    <label>Block ID</label>
                    <input type="text" id="prop-id" class="prop-input" />
                </div>
                <div class="prop-group">
                    <label>CSS Classes</label>
                    <input type="text" id="prop-class" class="prop-input" />
                </div>
                <div class="prop-group" id="group-text">
                    <label>Inner Text</label>
                    <textarea id="prop-text" class="prop-input prop-textarea"></textarea>
                </div>
                <div class="prop-group" id="group-form" style="display:none;">
                    <label>Select Form</label>
                    <select id="prop-form" class="prop-input"></select>
                </div>
                <div class="prop-group" id="group-data-provider" style="display:none;">
                    <label>Data Provider (Controller)</label>
                    <select id="prop-data-provider" class="prop-input">
                        <option value="">-- Manual Config --</option>
                    </select>
                </div>
                <div class="prop-group" id="group-data-source" style="display:none;">
                    <label>Data Source (Table Name)</label>
                    <input type="text" id="prop-data-source" class="prop-input" placeholder="e.g. tblAnalytics" />
                </div>
                <div class="prop-group" id="group-data-config" style="display:none;">
                    <label>Mapping Config (JSON)</label>
                    <textarea id="prop-data-config" class="prop-input prop-textarea" placeholder='{"mapping": {...}, "columns": [...]}'></textarea>
                </div>
                <div class="prop-group" id="group-chart-type" style="display:none;">
                    <label>Chart Type</label>
                    <select id="prop-chart-type" class="prop-input">
                        <option value="bar">Bar Chart</option>
                        <option value="line">Line Chart</option>
                        <option value="pie">Pie Chart</option>
                    </select>
                </div>
            </div>
            <div id="page-settings">
                <div class="sidebar-section-title">Page Settings</div>
                <div class="prop-group">
                    <label>Page Title</label>
                    <input type="text" id="page-title" class="prop-input" />
                </div>
                <div class="prop-group">
                    <label>Page Slug</label>
                    <input type="text" id="page-slug" class="prop-input" />
                </div>
                <div class="prop-group">
                    <label>Element Tree</label>
                    <div id="element-tree" class="tree-view"></div>
                </div>
            </div>
        `;

        this.propertiesPanel.appendChild(propContent);

        this.saveBtn = document.createElement('button');
        this.saveBtn.innerText = 'Save Workspace';
        this.saveBtn.className = 'save-btn';
        this.saveBtn.onclick = () => this.saveWorkspace();
        this.propertiesPanel.appendChild(this.saveBtn);

        const previewBtn = document.createElement('button');
        previewBtn.innerText = 'Preview Page';
        previewBtn.className = 'preview-btn';
        previewBtn.style.marginTop = '10px';
        previewBtn.onclick = () => {
            if (this.pageId === 0) {
                alert('Please save the page first before previewing.');
                return;
            }
            // Open in new tab. We'll use a preview route that handles the rendering.
            window.open(`/preview?id=${this.pageId}`, '_blank');
        };
        this.propertiesPanel.appendChild(previewBtn);

        // Append to container
        this.editorContainer.appendChild(this.palette);
        this.editorContainer.appendChild(this.canvasWrapper);
        this.editorContainer.appendChild(this.propertiesPanel);

        // Append to target
        this.container.appendChild(this.editorContainer);

        this.fetchForms();

        // Placeholder for drag & drop
        this.placeholder = document.createElement('div');
        this.placeholder.className = 'drop-placeholder';

        // Toolbar for rich text editing
        this.initToolbar();
    }

    async fetchForms() {
        try {
            const formData = new FormData();
            formData.append('csrf_token', this.csrfToken);
            const res = await fetch('/formAction', { method: "POST", body: formData });
            const data = await res.json();
            if (data.success) {
                this.forms = data.forms;
                this.updateFormSelect();
            }
        } catch (err) {
            console.error('Failed to fetch forms', err);
        }
    }

    updateFormSelect() {
        const select = document.getElementById('prop-form');
        if (!select) return;
        select.innerHTML = '<option value="">-- Select a Form --</option>';
        this.forms.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.tfPK;
            opt.innerText = f.tfName;
            select.appendChild(opt);
        });
    }

    initToolbar() {
        this.toolbar = document.createElement('div');
        this.toolbar.className = 'editor-toolbar';

        const commands = [
            { cmd: 'bold', label: 'B' },
            { cmd: 'italic', label: 'I' },
            { cmd: 'underline', label: 'U' },
            { cmd: 'insertUnorderedList', label: 'UL' }
        ];

        commands.forEach(c => {
            const btn = document.createElement('button');
            btn.className = 'toolbar-btn';
            btn.innerText = c.label;
            btn.title = c.cmd;
            btn.onmousedown = (e) => {
                e.preventDefault(); // keep focus on contenteditable
                document.execCommand(c.cmd, false, null);
            };
            this.toolbar.appendChild(btn);
        });

        // Separator
        const sep = document.createElement('div');
        sep.style.width = '1px';
        sep.style.background = 'rgba(255,255,255,0.2)';
        sep.style.margin = '0 5px';
        this.toolbar.appendChild(sep);

        // Move Up
        const upBtn = document.createElement('button');
        upBtn.className = 'toolbar-btn';
        upBtn.innerHTML = '↑';
        upBtn.title = 'Move Up';
        upBtn.onmousedown = (e) => {
            e.preventDefault();
            if (this.activeBlock && this.activeBlock.previousElementSibling) {
                this.activeBlock.parentNode.insertBefore(this.activeBlock, this.activeBlock.previousElementSibling);
                this.showToolbar(this.activeBlock);
            }
        };
        this.toolbar.appendChild(upBtn);

        // Move Down
        const downBtn = document.createElement('button');
        downBtn.className = 'toolbar-btn';
        downBtn.innerHTML = '↓';
        downBtn.title = 'Move Down';
        downBtn.onmousedown = (e) => {
            e.preventDefault();
            if (this.activeBlock && this.activeBlock.nextElementSibling) {
                this.activeBlock.parentNode.insertBefore(this.activeBlock.nextElementSibling, this.activeBlock);
                this.showToolbar(this.activeBlock);
            }
        };
        this.toolbar.appendChild(downBtn);

        // Drag Handle
        const dragBtn = document.createElement('button');
        dragBtn.className = 'toolbar-btn drag-btn';
        dragBtn.innerHTML = '⠿';
        dragBtn.title = 'Drag to Position';
        dragBtn.draggable = true;
        dragBtn.addEventListener('dragstart', (e) => {
            if (this.activeBlock) {
                this.draggedInternalBlock = this.activeBlock;
                e.dataTransfer.effectAllowed = 'move';
                this.activeBlock.classList.add('is-dragging');
            }
        });
        dragBtn.addEventListener('dragend', () => {
            if (this.draggedInternalBlock) {
                this.draggedInternalBlock.classList.remove('is-dragging');
                this.draggedInternalBlock = null;
            }
        });
        this.toolbar.appendChild(dragBtn);

        // Separator
        const sep2 = document.createElement('div');
        sep2.style.width = '1px';
        sep2.style.background = 'rgba(255,255,255,0.2)';
        sep2.style.margin = '0 5px';
        this.toolbar.appendChild(sep2);

        // Duplicate Block Action
        const dupBtn = document.createElement('button');
        dupBtn.className = 'toolbar-btn';
        dupBtn.innerHTML = '📋';
        dupBtn.title = 'Duplicate Block';
        dupBtn.onmousedown = (e) => {
            e.preventDefault();
            if (this.activeBlock) {
                this.duplicateBlock(this.activeBlock);
            }
        };
        this.toolbar.appendChild(dupBtn);

        // Delete Block Action
        const delBtn = document.createElement('button');
        delBtn.className = 'toolbar-btn del-btn';
        delBtn.innerHTML = '🗑️';
        delBtn.title = 'Delete Block';
        delBtn.onmousedown = (e) => {
            e.preventDefault();
            if (this.activeBlock) {
                this.removeBlock(this.activeBlock);
            }
        };
        this.toolbar.appendChild(delBtn);

        document.body.appendChild(this.toolbar);
    }

    bindEvents() {
        // Canvas Drag & Drop
        this.canvasContent.addEventListener('dragover', (e) => {
            e.preventDefault();

            const target = e.target.closest('.block-instance');
            const container = e.target.closest('.block-instance[data-block-type="container"]');

            if (this.draggedInternalBlock) {
                e.dataTransfer.dropEffect = 'move';
                if (target && target !== this.draggedInternalBlock) {
                    const rect = target.getBoundingClientRect();
                    const next = (e.clientY - rect.top) > (rect.height / 2);

                    // If target is a container AND we are hovering over its background/padding
                    if (target.dataset.blockType === 'container' && e.target === target) {
                        target.appendChild(this.draggedInternalBlock);
                    } else {
                        target.parentNode.insertBefore(this.draggedInternalBlock, next ? target.nextSibling : target);
                    }
                } else if (container && container !== this.draggedInternalBlock && !container.contains(this.draggedInternalBlock)) {
                    container.appendChild(this.draggedInternalBlock);
                }
            } else if (this.draggedBlockType) {
                e.dataTransfer.dropEffect = 'copy';
                this.placeholder.classList.add('is-active');

                if (target) {
                    const rect = target.getBoundingClientRect();
                    const next = (e.clientY - rect.top) > (rect.height / 2);

                    if (target.dataset.blockType === 'container' && e.target === target) {
                        target.appendChild(this.placeholder);
                    } else {
                        target.parentNode.insertBefore(this.placeholder, next ? target.nextSibling : target);
                    }
                } else if (container) {
                    container.appendChild(this.placeholder);
                } else {
                    this.canvasContent.appendChild(this.placeholder);
                }
            }
        });

        this.canvasContent.addEventListener('dragleave', (e) => {
            if (e.target === this.canvasContent) {
                // this.placeholder.classList.remove('is-active');
            }
        });

        this.canvasContent.addEventListener('drop', (e) => {
            e.preventDefault();

            let targetParent = this.canvasContent;
            let beforeElement = null;

            // Find where we are dropping
            const block = e.target.closest('.block-instance');

            if (block) {
                const rect = block.getBoundingClientRect();
                const next = (e.clientY - rect.top) > (rect.height / 2);

                if (block.dataset.blockType === 'container' && e.target === block) {
                    targetParent = block;
                    beforeElement = null;
                } else {
                    targetParent = block.parentNode;
                    beforeElement = next ? block.nextSibling : block;
                }
            } else {
                // If not directly on a block, maybe we are inside a container's empty area
                const container = e.target.closest('.block-instance[data-block-type="container"]');
                if (container) {
                    targetParent = container;
                    beforeElement = null;
                }
            }

            if (this.draggedBlockType) {
                this.addBlock(this.draggedBlockType, null, targetParent, beforeElement);
                this.draggedBlockType = null;
            }
            if (this.draggedInternalBlock) {
                this.draggedInternalBlock.classList.remove('is-dragging');
                this.showToolbar(this.draggedInternalBlock);
                this.draggedInternalBlock = null;
            }
            this.placeholder.classList.remove('is-active');
            if (this.placeholder.parentNode) {
                this.placeholder.parentNode.removeChild(this.placeholder);
            }
        });

        // Deselect if clicking directly on canvas content background
        this.canvasWrapper.addEventListener('click', (e) => {
            if (e.target === this.canvasContent || e.target === this.canvasWrapper) {
                this.selectBlock(null);
            }
        });

        // Properties Panel Sync
        const idInput = document.getElementById('prop-id');
        const classInput = document.getElementById('prop-class');
        const textInput = document.getElementById('prop-text');

        idInput.addEventListener('input', (e) => {
            if (this.activeBlock) this.activeBlock.id = e.target.value;
        });
        classInput.addEventListener('input', (e) => {
            if (this.activeBlock) {
                this.activeBlock.className = 'block-instance ' + e.target.value;
                if (this.activeBlock.classList.contains('is-selected')) {
                    // retain selection highlighting
                } else {
                    this.activeBlock.classList.add('is-selected');
                }
            }
        });
        textInput.addEventListener('input', (e) => {
            if (this.activeBlock) {
                this.activeBlock.innerText = e.target.value;
            }
        });

        const formInput = document.getElementById('prop-form');
        formInput.addEventListener('change', (e) => {
            if (this.activeBlock && this.activeBlock.dataset.blockType === 'form') {
                this.activeBlock.dataset.formId = e.target.value;
                const formName = e.target.options[e.target.selectedIndex].text;
                this.activeBlock.innerText = `[Form: ${formName}]`;
                this.renderTreeView();
            }
        });

        const sourceInput = document.getElementById('prop-data-source');
        sourceInput.addEventListener('input', (e) => {
            if (this.activeBlock) {
                this.activeBlock.dataset.dataSource = e.target.value;
                this.renderTreeView();
            }
        });

        const configInput = document.getElementById('prop-data-config');
        configInput.addEventListener('input', (e) => {
            if (this.activeBlock) {
                this.activeBlock.dataset.dataConfig = e.target.value;
            }
        });

        const providerInput = document.getElementById('prop-data-provider');
        providerInput.addEventListener('change', (e) => {
            if (this.activeBlock) {
                this.activeBlock.dataset.dataProvider = e.target.value;
                if (e.target.value) {
                    this.activeBlock.innerText = `[Table: ${e.target.options[e.target.selectedIndex].text}]`;
                } else {
                    this.activeBlock.innerText = `[Table: ${this.activeBlock.dataset.dataSource || 'New'}]`;
                }
                this.renderTreeView();
                this.selectBlock(this.activeBlock); // Refresh visibility
            }
        });

        const chartTypeInput = document.getElementById('prop-chart-type');
        chartTypeInput.addEventListener('change', (e) => {
            if (this.activeBlock) {
                this.activeBlock.dataset.chartType = e.target.value;
                this.renderTreeView();
            }
        });
    }

    addBlock(type, data = null, targetParent = null, beforeElement = null) {
        const el = document.createElement('div');
        el.className = 'block-instance';
        el.dataset.blockType = type;

        if (data) {
            el.dataset.elePk = data.elePK;
            if (type !== 'container' && type !== 'form') {
                el.innerText = data.eleContent;
            }
            if (type === 'form') {
                el.dataset.formId = data.eleContent;
                // We'll need a way to resolve the name, for now just show ID
                el.innerText = `[Form ID: ${data.eleContent}]`;
            }
            if (['table', 'chart'].includes(type)) {
                try {
                    // Decode HTML entities (like &quot;) before parsing JSON
                    const textArea = document.createElement('textarea');
                    textArea.innerHTML = data.eleContent;
                    const decodedContent = textArea.value;

                    const config = JSON.parse(decodedContent);
                    el.dataset.dataProvider = config.dataProvider || '';
                    el.dataset.dataSource = config.source || '';
                    el.dataset.dataConfig = config.config || '';
                    el.dataset.chartType = config.chartType || 'bar';

                    if (el.dataset.dataProvider) {
                        el.innerText = `[Table: ${el.dataset.dataProvider}]`;
                    } else {
                        el.innerText = `[${type === 'table' ? 'Table' : 'Chart'}: ${el.dataset.dataSource}]`;
                    }
                } catch (e) {
                    console.error('Failed to parse block content as JSON', data.eleContent);
                }
            }
            if (data.eleCSSClasses) {
                data.eleCSSClasses.split(' ').forEach(c => {
                    if (c) el.classList.add(c);
                });
            }
        } else {
            switch (type) {
                case 'heading':
                    el.innerText = 'New Heading';
                    break;
                case 'paragraph':
                    el.innerText = 'Lorem ipsum dolor sit amet...';
                    break;
                case 'divider':
                    break;
                case 'container':
                    break;
                case 'form':
                    el.innerText = '[Select a Form]';
                    break;
                case 'button':
                    el.innerText = 'Click Me';
                    break;
                case 'table':
                    el.innerText = '[New Data Table]';
                    break;
                case 'chart':
                    el.innerText = '[New Data Chart]';
                    break;
            }
        }

        // Selection Event
        el.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectBlock(el);
        });

        // Inline Editing Event
        el.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            if (['heading', 'paragraph'].includes(type)) {
                el.contentEditable = true;
                el.focus();
                this.showToolbar(el);
            }
        });

        el.addEventListener('blur', () => {
            el.contentEditable = false;
            this.toolbar.classList.remove('is-active');
            if (this.activeBlock === el) {
                document.getElementById('prop-text').value = el.innerText;
            }
        });

        // Insert into the DOM
        const parent = targetParent || this.canvasContent;
        if (beforeElement && beforeElement.parentNode === parent) {
            parent.insertBefore(el, beforeElement);
        } else {
            parent.appendChild(el);
        }

        if (!data) {
            this.selectBlock(el);
        }
        return el;
    }

    getElementTagForType(type) {
        switch (type) {
            case 'heading': return 'h2';
            case 'paragraph': return 'p';
            case 'container': return 'div';
            case 'button': return 'button';
            case 'divider': return 'div';
            case 'form': return 'div';
            default: return 'div';
        }
    }

    selectBlock(el) {
        if (this.activeBlock) {
            this.activeBlock.classList.remove('is-selected');
            this.activeBlock.contentEditable = false;
        }

        this.activeBlock = el;
        const propContainer = document.getElementById('prop-container');
        const pageSettings = document.getElementById('page-settings');
        const textGroup = document.getElementById('group-text');
        const formGroup = document.getElementById('group-form');
        const sourceGroup = document.getElementById('group-data-source');
        const configGroup = document.getElementById('group-data-config');
        const chartTypeGroup = document.getElementById('group-chart-type');

        if (el) {
            el.classList.add('is-selected');
            propContainer.style.display = 'block';
            pageSettings.style.display = 'none';

            document.getElementById('prop-type').value = el.dataset.blockType;
            document.getElementById('prop-id').value = el.id || '';
            document.getElementById('prop-class').value = Array.from(el.classList)
                .filter(c => c !== 'is-selected' && c !== 'block-instance')
                .join(' ');

            // Show/Hide groups based on type
            const type = el.dataset.blockType;

            if (['heading', 'paragraph', 'button'].includes(type)) {
                textGroup.style.display = 'block';
                document.getElementById('prop-text').value = el.innerText;
            } else {
                textGroup.style.display = 'none';
            }

            if (type === 'form') {
                formGroup.style.display = 'block';
                document.getElementById('prop-form').value = el.dataset.formId || '';
            } else {
                formGroup.style.display = 'none';
            }

            if (['table', 'chart'].includes(type)) {
                const providerGroup = document.getElementById('group-data-provider');
                providerGroup.style.display = 'block';
                document.getElementById('prop-data-provider').value = el.dataset.dataProvider || '';

                const hasProvider = !!el.dataset.dataProvider;
                sourceGroup.style.display = hasProvider ? 'none' : 'block';
                configGroup.style.display = hasProvider ? 'none' : 'block';

                document.getElementById('prop-data-source').value = el.dataset.dataSource || '';
                document.getElementById('prop-data-config').value = el.dataset.dataConfig || '';
            } else {
                document.getElementById('group-data-provider').style.display = 'none';
                sourceGroup.style.display = 'none';
                configGroup.style.display = 'none';
            }

            if (type === 'chart') {
                chartTypeGroup.style.display = 'block';
                document.getElementById('prop-chart-type').value = el.dataset.chartType || 'bar';
            } else {
                chartTypeGroup.style.display = 'none';
            }

            // Show toolbar on selection for block actions (like delete)
            this.showToolbar(el);
        } else {
            propContainer.style.display = 'none';
            pageSettings.style.display = 'block';
            this.toolbar.classList.remove('is-active');

            // Populate page settings
            document.getElementById('page-title').value = this.pageTitle;
            document.getElementById('page-slug').value = this.pageSlug;
            this.renderTreeView();
        }
    }

    renderTreeView() {
        const treeContainer = document.getElementById('element-tree');
        if (!treeContainer) return;
        treeContainer.innerHTML = '';

        const buildTree = (parentEl, container) => {
            const children = Array.from(parentEl.children).filter(el => el.classList.contains('block-instance'));
            if (children.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'tree-empty';
                empty.innerText = 'No blocks yet.';
                container.appendChild(empty);
                return;
            }

            const ul = document.createElement('ul');
            ul.className = 'tree-list';

            children.forEach(child => {
                const li = document.createElement('li');
                li.className = 'tree-item';

                const label = document.createElement('div');
                label.className = 'tree-label';
                label.innerHTML = `<span class="tree-icon">${this.getIconForType(child.dataset.blockType)}</span> ${child.dataset.blockType}`;
                label.onclick = (e) => {
                    e.stopPropagation();
                    this.selectBlock(child);
                };

                li.appendChild(label);

                // Nesting
                const nestedContainer = document.createElement('div');
                buildTree(child, nestedContainer);
                if (nestedContainer.querySelector('.tree-list')) {
                    li.appendChild(nestedContainer);
                }

                ul.appendChild(li);
            });
            container.appendChild(ul);
        };

        buildTree(this.canvasContent, treeContainer);
    }

    getIconForType(type) {
        switch (type) {
            case 'heading': return 'H';
            case 'paragraph': return '¶';
            case 'container': return '📦';
            case 'button': return '🔘';
            case 'divider': return '➖';
            case 'form': return '📋';
            case 'table': return '📊';
            case 'chart': return '📈';
            default: return '📄';
        }
    }

    async removeBlock(el) {
        if (!el) return;
        const elePk = el.dataset.elePk;
        if (elePk) {
            const data = new FormData();
            data.append('action', 'delete');
            data.append('elePK', elePk);
            data.append('csrf_token', this.csrfToken);
            try {
                await fetch('/elementAction', {
                    method: 'POST',
                    body: data,
                    headers: {
                        'Accept': 'application/json'
                    }
                });
            } catch (err) {
                console.error('Failed to delete block from server', err);
            }
        }
        el.remove();
        this.selectBlock(null);
    }

    duplicateBlock(el) {
        const clone = el.cloneNode(true);

        const bindCloneEvents = (node) => {
            if (node.classList && node.classList.contains('block-instance')) {
                delete node.dataset.elePk;
                node.classList.remove('is-selected');
                const type = node.dataset.blockType;

                node.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectBlock(node);
                });
                node.addEventListener('dblclick', (e) => {
                    e.stopPropagation();
                    if (['heading', 'paragraph'].includes(type)) {
                        node.contentEditable = true;
                        node.focus();
                        this.showToolbar(node);
                    }
                });
                node.addEventListener('blur', () => {
                    node.contentEditable = false;
                    this.toolbar.classList.remove('is-active');
                    if (this.activeBlock === node) {
                        document.getElementById('prop-text').value = node.innerText;
                    }
                    this.renderTreeView();
                });
            }
            Array.from(node.children).forEach(bindCloneEvents);
        };

        bindCloneEvents(clone);
        el.parentNode.insertBefore(clone, el.nextSibling);
        this.selectBlock(clone);
    }

    async saveWorkspace() {
        if (this.isSaving) return;
        this.isSaving = true;
        this.saveBtn.disabled = true;
        this.saveBtn.classList.add('is-saving');

        const blocks = Array.from(this.canvasContent.querySelectorAll('.block-instance'));

        // Update page title/slug from inputs
        this.pageTitle = document.getElementById('page-title').value;
        this.pageSlug = document.getElementById('page-slug').value;

        try {
            if (this.pageId === 0) {
                if (!this.pageTitle) {
                    alert('Please enter a page title.');
                    this.isSaving = false;
                    this.saveBtn.disabled = false;
                    this.saveBtn.classList.remove('is-saving');
                    return;
                }
                this.saveBtn.innerText = 'Creating Page...';
                const pData = new FormData();
                pData.append('pagTitle', this.pageTitle);
                pData.append('pagSlug', this.pageSlug || this.pageTitle.toLowerCase().replace(/ /g, '-'));
                pData.append('csrf_token', this.csrfToken);

                const pRes = await fetch('/pageAction', {
                    method: 'POST',
                    body: pData,
                    headers: { 'Accept': 'application/json' }
                });
                const pResult = await pRes.json();
                if (pResult.success && pResult.eventId) {
                    location.href = '/page_management';
                    return;
                }
            } else {
                this.saveBtn.innerText = 'Updating Page...';
                // Update existing page title/slug
                const pData = new FormData();
                pData.append('pagPK', this.pageId);
                pData.append('pagTitle', this.pageTitle);
                pData.append('pagSlug', this.pageSlug);
                pData.append('csrf_token', this.csrfToken);

                await fetch('/pageAction', {
                    method: 'POST',
                    body: pData,
                    headers: { 'Accept': 'application/json' }
                });
            }

            const total = blocks.length;
            let current = 0;

            for (const block of blocks) {
                current++;
                this.saveBtn.innerText = `Saving... (${current}/${total})`;

                const data = new FormData();
                data.append('elePK', block.dataset.elePk || 0);
                data.append('eleType', block.dataset.blockType);

                // Only save text content for non-container/non-form types to avoid duplication
                if (block.dataset.blockType === 'form') {
                    data.append('eleContent', block.dataset.formId || '');
                } else if (['table', 'chart'].includes(block.dataset.blockType)) {
                    const config = {
                        dataProvider: block.dataset.dataProvider || '',
                        source: block.dataset.dataSource || '',
                        config: block.dataset.dataConfig || '',
                        chartType: block.dataset.chartType || 'bar'
                    };
                    data.append('eleContent', JSON.stringify(config));
                } else if (block.dataset.blockType !== 'container') {
                    data.append('eleContent', block.innerText);
                } else {
                    data.append('eleContent', '');
                }

                data.append('eleCSSClasses', Array.from(block.classList).filter(c => c !== 'is-selected' && c !== 'block-instance').join(' '));
                data.append('pageId', this.pageId);
                data.append('pelOrder', current * 10);
                data.append('csrf_token', this.csrfToken);

                // Determine if nested
                const parentBlock = block.parentNode.closest('.block-instance');
                if (parentBlock && parentBlock.dataset.elePk) {
                    data.append('eleParentFK', parentBlock.dataset.elePk);
                } else {
                    data.append('eleParentFK', 0);
                }

                try {
                    const response = await fetch('/elementAction', {
                        method: 'POST',
                        body: data,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const result = await response.json();
                    if (result.success && result.elePK) {
                        block.dataset.elePk = result.elePK;
                    }
                } catch (err) {
                    console.error('Failed to save block', block.id, err);
                }
            }
        } finally {
            this.isSaving = false;
            this.saveBtn.disabled = false;
            this.saveBtn.innerText = 'Save Workspace';
            this.saveBtn.classList.remove('is-saving');
        }
    }

    showToolbar(el) {
        const rect = el.getBoundingClientRect();
        this.toolbar.style.top = (rect.top - 50) + 'px';
        this.toolbar.style.left = rect.left + 'px';
        this.toolbar.classList.add('is-active');
    }
}

// Setup the mount
document.addEventListener('DOMContentLoaded', () => {
    //const root = document.getElementById();
    //if (root) {
    new BlockEditor('editor-root');
    //}
});

export default BlockEditor;
