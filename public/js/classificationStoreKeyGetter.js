pimcore.registerNS("pimcore.object.gridcolumn.operator.classificationstorekeygetter");

/**
 * Grid column operator: ClassificationStore Key Getter
 *
 * Retrieves a classification store value by key, searching across all groups
 * and returning the first non-empty value found — giving a single consolidated
 * column for a key like "Accessory Type" rather than one column per group.
 */
pimcore.object.gridcolumn.operator.classificationstorekeygetter = Class.create(pimcore.object.gridcolumn.Abstract, {
    operatorGroup: "extractor",
    type: "operator",
    class: "ClassificationStoreKeyGetter",
    iconCls: "pimcore_icon_classificationstore",
    defaultText: "ClassificationStore Key Getter",
    group: "getter",

    getConfigTreeNode: function (configAttributes) {
        let node;
        if (configAttributes) {
            node = {
                draggable: true,
                iconCls: this.iconCls,
                text: this.getNodeLabel(configAttributes),
                configAttributes: configAttributes,
                isTarget: true,
                maxChildCount: 0,
                expanded: false,
                leaf: true,
                expandable: false
            };
        } else {
            const defaultConfig = {type: this.type, class: this.class, label: this.getDefaultText()};
            node = {
                draggable: true,
                iconCls: this.iconCls,
                text: this.getDefaultText(),
                configAttributes: defaultConfig,
                isTarget: true,
                maxChildCount: 0,
                leaf: true
            };
        }
        node.isOperator = true;
        return node;
    },

    getCopyNode: function (source) {
        const copy = source.createNode({
            iconCls: this.iconCls,
            text: source.data.cssClass,
            isTarget: true,
            leaf: true,
            maxChildCount: 0,
            expanded: false,
            isOperator: true,
            configAttributes: {
                label: source.data.configAttributes.label,
                type: this.type,
                class: this.class,
            },
        });
        return copy;
    },

    getConfigDialog: function (node, params) {
        this.node = node;

        const me = this;

        const savedLabel = this.node.data.configAttributes.label || '';
        const savedKeyName = this.node.data.configAttributes.keyName || '';
        // Auto-manage label when it's blank, default, or matches the last selected key.
        this.labelAutoManaged = !savedLabel || savedLabel === this.getDefaultText() || savedLabel === savedKeyName;
        this.labelAutoSetting = false;

        this.labelSyncNote = new Ext.form.DisplayField({
            value: '&#9888; Auto-sync paused &mdash; clear the label to resume',
            hidden: this.labelAutoManaged,
            fieldStyle: 'color:#b36200; font-size:11px; padding-top:0;',
            margin: '-6 0 4 108',
        });

        this.labelField = new Ext.form.TextField({
            fieldLabel: t('label'),
            width: 300,
            value: savedLabel,
            renderer: Ext.util.Format.htmlEncode,
            listeners: {
                change: (field, newValue) => {
                    if (!me.labelAutoSetting) {
                        me.labelAutoManaged = false;
                        me.labelSyncNote.setVisible(true);
                    }
                    // Clearing the field re-enables auto-sync.
                    if (!newValue) {
                        me.labelAutoManaged = true;
                        me.labelSyncNote.setVisible(false);
                    }
                },
            },
        });

        // Build a local store (instance var so storeCombo listener can access it),
        // then populate by walking the class definition.
        this.csFieldStore = new Ext.data.Store({
            fields: ['name', 'title', 'storeId'],
            data: [],
        });

        this.csFieldNameField = new Ext.form.ComboBox({
            fieldLabel: 'Field Name',
            store: this.csFieldStore,
            displayField: 'title',
            valueField: 'name',
            width: 300,
            value: this.node.data.configAttributes.csFieldName || null,
            emptyText: 'Loading…',
            editable: false,
            queryMode: 'local',
            triggerAction: 'all',
            forceSelection: false,
        });

        // Fetch the class definition and find all classificationstore fields.
        if (this.objectClassId) {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_class_get'),
                params: {id: this.objectClassId},
                success: (response) => {
                    const data = Ext.decode(response.responseText);
                    const csFields = [];
                    const walk = (children) => {
                        if (!children) {
                            return;
                        }
                        children.forEach((child) => {
                            if (child.fieldtype === 'classificationstore') {
                                csFields.push({name: child.name, title: `${child.title || child.name} (${child.name})`, storeId: child.storeId || null});
                            }
                            if (child.children) {
                                walk(child.children);
                            }
                        });
                    };
                    walk(data.layoutDefinitions && data.layoutDefinitions.children);
                    this.csFieldStore.loadData(csFields);
                    this.csFieldNameField.emptyText = csFields.length ? '(select a field)' : '(none found)';
                    // Re-apply the saved value now that the store is loaded.
                    const saved = this.node.data.configAttributes.csFieldName;
                    if (saved) {
                        this.csFieldNameField.setValue(saved);
                    } else if (this.node.data.configAttributes.storeId) {
                        // No field saved yet — derive it from the one-to-one store mapping.
                        const fieldRec = this.csFieldStore.findRecord('storeId', this.node.data.configAttributes.storeId);
                        if (fieldRec) {
                            this.csFieldNameField.setValue(fieldRec.get('name'));
                        }
                    }
                },
            });
        } else {
            this.csFieldNameField.emptyText = 'No class ID available';
        }

        // --- Store selection ---
        this.storeStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_dataobject_classificationstore_liststores'),
                reader: {type: 'json'},
            },
            fields: ['id', 'name'],
            autoLoad: true,
            listeners: {
                load: function () {
                    if (me.node.data.configAttributes.storeId) {
                        me.storeCombo.setValue(me.node.data.configAttributes.storeId);
                    }
                },
            },
        });

        this.storeCombo = new Ext.form.ComboBox({
            fieldLabel: t('store'),
            store: me.storeStore,
            displayField: 'name',
            valueField: 'id',
            width: 300,
            value: this.node.data.configAttributes.storeId || null,
            editable: false,
            queryMode: 'local',
            triggerAction: 'all',
            forceSelection: true,
            listeners: {
                select: function (combo) {
                    me.selectedKeyId = null;
                    me.selectedKeyName = null;
                    me.keyDisplayField.setValue('');
                    // Clear auto-managed label when store changes.
                    if (me.labelAutoManaged) {
                        me.labelAutoSetting = true;
                        me.labelField.setValue('');
                        me.labelAutoSetting = false;
                    }
                    // Auto-set the CS field that maps one-to-one to this store.
                    const fieldRec = me.csFieldStore.findRecord('storeId', combo.getValue());
                    if (fieldRec) {
                        me.csFieldNameField.setValue(fieldRec.get('name'));
                    }
                },
            },
        });

        // --- Key selection (read-only display + search button opening a modal grid) ---
        this.selectedKeyId = this.node.data.configAttributes.keyId || null;
        this.selectedKeyName = this.node.data.configAttributes.keyName || null;

        this.keyDisplayField = new Ext.form.TextField({
            width: 230,
            readOnly: true,
            value: this.selectedKeyName || '',
            emptyText: '(none selected)',
            flex: 1,
        });

        this.keySearchButton = new Ext.button.Button({
            text: t('search'),
            iconCls: 'pimcore_icon_search',
            style: 'margin-left: 5px;',
            handler: function () {
                me.openKeySearchWindow(me.storeCombo.getValue() || null);
            },
        });

        this.keyContainer = new Ext.form.FieldContainer({
            fieldLabel: 'Selected Key',
            layout: 'hbox',
            items: [this.keyDisplayField, this.keySearchButton],
        });

        this.configPanel = new Ext.form.Panel({
            layout: 'form',
            bodyStyle: 'padding: 10px;',
            items: [this.labelField, this.labelSyncNote, this.csFieldNameField, this.storeCombo, this.keyContainer],
            buttons: [{
                text: t('apply'),
                iconCls: 'pimcore_icon_apply',
                handler: function () {
                    me.commitData(params);
                },
            }],
        });

        this.window = new Ext.Window({
            width: 480,
            height: 320,
            modal: true,
            title: t('settings'),
            layout: 'fit',
            items: [this.configPanel],
        });

        this.window.show();
        return this.window;
    },

    /**
     * Opens a paginated, searchable Search Key modal.
     * storeId is optional — if null, keys from all stores are shown.
     * On selection the store combo is auto-set from the key's storeId.
     */
    openKeySearchWindow: function (storeId = null) {
        const me = this;

        const searchField = new Ext.form.field.Text({
            width: 200,
            emptyText: t('search') + '...',
            enableKeyEvents: true,
            listeners: {
                keypress: function (field, e) {
                    if (e.getKey() === 13) {
                        keyGridStore.getProxy().setExtraParam('searchfilter', field.getValue());
                        keyGridStore.loadPage(1);
                    }
                },
            },
        });

        const extraParams = storeId ? {storeId} : {};

        const keyGridStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_dataobject_classificationstore_propertiesget'),
                extraParams,
                reader: {
                    type: 'json',
                    rootProperty: 'data',
                    totalProperty: 'total',
                },
            },
            fields: ['id', 'name', 'description', 'storeId'],
            remoteSort: true,
            pageSize: 25,
            autoLoad: true,
        });

        const storeNameRenderer = (value) => {
            const rec = me.storeStore.findRecord('id', value);
            return rec ? rec.get('name') : (value || '');
        };

        const keyGrid = new Ext.grid.Panel({
            store: keyGridStore,
            columns: [
                {text: 'ID', dataIndex: 'id', width: 60, sortable: true},
                {text: t('name'), dataIndex: 'name', flex: 1, sortable: true},
                {text: t('description'), dataIndex: 'description', flex: 1, sortable: true},
                {text: t('store'), dataIndex: 'storeId', width: 120, sortable: false, renderer: storeNameRenderer},
            ],
            selModel: Ext.create('Ext.selection.RowModel', {mode: 'SINGLE'}),
            border: false,
            columnLines: true,
            stripeRows: true,
            bbar: pimcore.helpers.grid.buildDefaultPagingToolbar(keyGridStore, {pageSize: 25}),
            tbar: [
                '->', `${t('search')}:`, searchField,
                {
                    xtype: 'button',
                    text: t('search'),
                    iconCls: 'pimcore_icon_search',
                    handler: function () {
                        keyGridStore.getProxy().setExtraParam('searchfilter', searchField.getValue());
                        keyGridStore.loadPage(1);
                    },
                },
            ],
            listeners: {
                rowdblclick: function (grid, record) {
                    me.applyKeyFromSearch(record, searchWindow);
                },
            },
        });

        const searchWindow = new Ext.window.Window({
            title: 'Search Key',
            width: 700,
            height: 500,
            modal: true,
            layout: 'fit',
            items: [keyGrid],
            bbar: [
                '->', {
                    text: t('cancel'),
                    iconCls: 'pimcore_icon_cancel',
                    handler: function () {
                        searchWindow.close();
                    },
                }, {
                    text: t('apply'),
                    iconCls: 'pimcore_icon_apply',
                    handler: function () {
                        const selected = keyGrid.getSelectionModel().getSelection();
                        if (selected.length === 0) {
                            Ext.MessageBox.alert(t('error'), 'Please select a key.');
                            return;
                        }
                        me.applyKeyFromSearch(selected[0], searchWindow);
                    },
                },
            ],
        });

        searchWindow.show();
    },

    applyKeyFromSearch: function (record, searchWindow) {
        this.selectedKeyId = record.get('id');
        this.selectedKeyName = record.get('name');
        this.keyDisplayField.setValue(this.selectedKeyName);

        // Auto-populate the store combo and CS field from the key's storeId.
        const keyStoreId = record.get('storeId');
        if (keyStoreId) {
            this.storeCombo.setValue(keyStoreId);
            const fieldRec = this.csFieldStore.findRecord('storeId', keyStoreId);
            if (fieldRec) {
                this.csFieldNameField.setValue(fieldRec.get('name'));
            }
        }

        // Auto-set the label to the key name unless the user has customised it.
        if (this.labelAutoManaged) {
            this.labelAutoSetting = true;
            this.labelField.setValue(this.selectedKeyName);
            this.labelAutoSetting = false;
            // Keep labelAutoManaged=true so future key selections can still update it.
            this.labelSyncNote.setVisible(false);
        }

        searchWindow.close();
    },

    commitData: function (params) {
        this.node.set('isOperator', true);
        const labelValue = this.labelField.getValue() || this.selectedKeyName || '';
        this.node.data.configAttributes.label = labelValue;
        this.node.data.configAttributes.csFieldName = this.csFieldNameField.getValue();
        this.node.data.configAttributes.storeId = this.storeCombo.getValue();
        this.node.data.configAttributes.keyId = this.selectedKeyId;
        this.node.data.configAttributes.keyName = this.selectedKeyName;

        this.node.set('text', this.getNodeLabel(this.node.data.configAttributes));
        this.window.close();

        if (params && params.callback) {
            params.callback();
        }
    },

    getNodeLabel: function (configAttributes) {
        let nodeLabel = configAttributes.label ? configAttributes.label : this.getDefaultText();
        if (configAttributes.keyName) {
            nodeLabel += `<span class="pimcore_gridnode_hint"> (${configAttributes.csFieldName || ''} \u2192 ${configAttributes.keyName})</span>`;
        }
        return nodeLabel;
    },
});
