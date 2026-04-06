pimcore.registerNS("pimcore.object.tags.arrayField");

pimcore.object.tags.arrayField = Class.create(pimcore.object.tags.abstract, {
    type: "arrayField",
    dirty: false,

    initialize: function (data, fieldConfig) {
        this.data = data || [];
        this.currentElements = [];
        this.fieldConfig = fieldConfig || {};
        this.dirty = false;
    },

    getGridColumnConfig: function (field) {
        return {
            text: t(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);
                // Backend already returns comma-delimited string
                return value || '';
            }.bind(this, field.key)
        };
    },

    getGridColumnEditor: function (field) {
        return null;
    },

    getGridColumnFilter: function (field) {
        return { type: 'string', dataIndex: field.key };
    },

    getLayoutEdit: function () {
        const readOnly = this.fieldConfig.noteditable;

        this.component = new Ext.Panel({
            autoHeight: true,
            border: false,
            style: "margin-bottom: 10px",
            componentCls: this.getWrapperClassNames(),
            bodyStyle: "padding: 0;",
            title: this.fieldConfig.title || null,
            items: []
        });

        if (!this.data || this.data.length < 1) {
            if (!readOnly) {
                this.component.add(this.createAddButton());
            }
        } else {
            for (let i = 0; i < this.data.length; i++) {
                const value = this.data[i].value !== undefined ? this.data[i].value : this.data[i];
                this.addItem(value);
            }
        }

        return this.component;
    },

    getLayoutShow: function () {
        this.component = new Ext.Panel({
            autoHeight: true,
            border: false,
            style: "margin-bottom: 10px",
            componentCls: this.getWrapperClassNames(),
            bodyStyle: "padding: 0;",
            title: this.fieldConfig.title || null,
            items: []
        });

        if (!this.data || this.data.length < 1) {
            this.component.add({
                xtype: 'panel',
                border: false,
                html: '<div style="padding: 10px; color: #999; font-style: italic;">No items</div>'
            });
        } else {
            for (let i = 0; i < this.data.length; i++) {
                const value = this.data[i].value !== undefined ? this.data[i].value : this.data[i];
                this.addItem(value, true);
            }
        }

        return this.component;
    },

    createAddButton: function () {
        return {
            xtype: 'button',
            cls: "pimcore_block_button_plus",
            iconCls: "pimcore_icon_plus",
            text: "Add Item",
            style: "margin: 5px 0;",
            handler: this.addItem.bind(this, null, false)
        };
    },

    getControls: function (itemContainer) {
        if (this.fieldConfig.noteditable) {
            return [];
        }

        return [
            {
                xtype: 'button',
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus_up",
                handler: this.addItemAbove.bind(this, itemContainer),
                style: "margin-left: 5px;"
            },
            {
                xtype: 'button',
                cls: "pimcore_block_button_plus",
                iconCls: "pimcore_icon_plus_down",
                handler: this.addItemBelow.bind(this, itemContainer),
                style: "margin-left: 2px;"
            },
            {
                xtype: 'tbspacer',
                width: 10
            },
            {
                xtype: 'button',
                cls: "pimcore_block_button_minus",
                iconCls: "pimcore_icon_delete",
                handler: this.removeItem.bind(this, itemContainer),
                style: "margin-left: 2px;"
            }
        ];
    },

    addItem: function (value, isReadOnlyView) {
        const addButton = this.component.items.findBy(function (item) {
            return item.iconCls === "pimcore_icon_plus" && item.text === "Add Item";
        });
        if (addButton) {
            this.component.remove(addButton, true);
        }

        const key = "item-" + new Date().getTime() + "-" + Math.floor(Math.random() * 1000);
        const readOnly = this.fieldConfig.noteditable || isReadOnlyView || false;
        const field = this.createElementField(value, readOnly);

        const itemContainer = new Ext.container.Container({
            key: key,
            layout: 'hbox',
            style: "margin-bottom: 5px;",
            items: [field]
        });
        
        // Add controls after container is created so the reference is valid
        if (!readOnly) {
            itemContainer.add(this.getControls(itemContainer));
        }

        this.currentElements.push({
            container: itemContainer,
            field: field,
            key: key
        });

        this.component.add(itemContainer);
        this.component.updateLayout();

        if (!isReadOnlyView && value !== null && value !== undefined) {
            this.dirty = true;
        }
    },

    addItemAbove: function (referenceItem) {
        const index = this.component.items.indexOf(referenceItem);
        this.insertItemAt(index);
    },

    addItemBelow: function (referenceItem) {
        const index = this.component.items.indexOf(referenceItem);
        this.insertItemAt(index + 1);
    },

    insertItemAt: function (index) {
        const key = "item-" + new Date().getTime() + "-" + Math.floor(Math.random() * 1000);
        const field = this.createElementField("", false);

        const itemContainer = new Ext.container.Container({
            key: key,
            layout: 'hbox',
            style: "margin-bottom: 5px;",
            items: [field]
        });
        
        // Add controls after container is created so the reference is valid
        itemContainer.add(this.getControls(itemContainer));

        this.currentElements.push({
            container: itemContainer,
            field: field,
            key: key
        });

        this.component.insert(index, itemContainer);
        this.component.updateLayout();
        this.dirty = true;
    },

    removeItem: function (itemContainer) {
        for (let i = 0; i < this.currentElements.length; i++) {
            if (this.currentElements[i].container === itemContainer) {
                this.currentElements.splice(i, 1);
                break;
            }
        }

        this.component.remove(itemContainer, true);
        this.dirty = true;

        if (this.currentElements.length === 0 && !this.fieldConfig.noteditable) {
            this.component.removeAll();
            this.component.add(this.createAddButton());
        }

        this.component.updateLayout();
    },

    createElementField: function (value, readOnly) {
        value = value !== undefined ? value : "";
        const elementType = this.fieldConfig.elementType || "input";

        switch (elementType) {
            case "textarea":
                return new Ext.form.TextArea({
                    value: value,
                    flex: 1,
                    height: 60,
                    readOnly: readOnly
                });

            case "numeric":
                return new Ext.form.NumberField({
                    value: value,
                    flex: 1,
                    readOnly: readOnly
                });

            default:
                return new Ext.form.TextField({
                    value: value,
                    flex: 1,
                    readOnly: readOnly
                });
        }
    },

    getValue: function () {
        const values = [];

        for (let i = 0; i < this.currentElements.length; i++) {
            const field = this.currentElements[i].field;
            const value = field.getSubmitValue ? field.getSubmitValue() : field.getValue();

            values.push({
                index: i,
                value: value
            });
        }

        return values;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function () {
        if (this.dirty) {
            return true;
        }

        for (let i = 0; i < this.currentElements.length; i++) {
            const field = this.currentElements[i].field;
            if (field.isDirty && field.isDirty()) {
                return true;
            }
        }

        return false;
    },

    isInvalidMandatory: function () {
        if (!this.fieldConfig.mandatory) {
            return false;
        }

        const values = this.getValue();
        return !values || values.length === 0;
    }
});
