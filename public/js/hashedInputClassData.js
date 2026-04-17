pimcore.registerNS("pimcore.object.classes.data.hashedInput");

pimcore.object.classes.data.hashedInput = Class.create(pimcore.object.classes.data.input, {

    type: "hashedInput",

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: false,
        classificationstore: false,
        block: true,
        encryptedField: false
    },

    initialize: function (treeNode, initData) {
        this.type = "hashedInput";
        this.initData(initData);
        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return "Hashed Input";
    },

    getGroup: function () {
        return "text";
    },

    getIconClass: function () {
        return "pimcore_icon_hashedInput";
    },

    // Hashed fields have no configurable regex or char-count settings
    getSpecificPanelItems: function (datax, inEncryptedField) {
        return [];
    },

    applySpecialData: function (source) {}
});
