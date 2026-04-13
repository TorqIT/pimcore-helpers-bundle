pimcore.registerNS("pimcore.object.classes.data.arrayField");

pimcore.object.classes.data.arrayField = Class.create(pimcore.object.classes.data.data, {

    type: "arrayField",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: false,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "arrayField";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return "Array Field";
    },

    getGroup: function () {
        return "structured";
    },

    getIconClass: function () {
        return "pimcore_icon_arrayField";
    },

    getLayout: function ($super) {
        $super();

        this.specificPanel.removeAll();

        const specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        return [
            {
                xtype: "fieldset",
                title: "Array Field Settings",
                collapsible: false,
                autoHeight: true,
                labelWidth: 150,
                defaultType: 'textfield',
                defaults: { width: 300 },
                items: [
                    {
                        xtype: "combo",
                        fieldLabel: "Element Type",
                        name: "elementType",
                        value: datax.elementType || "input",
                        store: [
                            ["input", "Input"],
                            ["textarea", "Textarea"],
                            ["numeric", "Numeric"]
                        ],
                        mode: "local",
                        triggerAction: "all",
                        editable: false
                    },
                    {
                        xtype: "checkbox",
                        fieldLabel: "Remove Duplicates",
                        name: "removeDuplicates",
                        checked: datax.removeDuplicates || false
                    },
                    {
                        xtype: "checkbox",
                        fieldLabel: "Filter Empty Values",
                        name: "filterEmptyValues",
                        checked: datax.filterEmptyValues || false
                    }
                ]
            }
        ];
    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }
            Ext.apply(this.datax, {
                elementType: source.datax.elementType,
                removeDuplicates: source.datax.removeDuplicates,
                filterEmptyValues: source.datax.filterEmptyValues
            });
        }
    }
});
