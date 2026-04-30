pimcore.registerNS("pimcore.object.classes.data.freeSolo");

pimcore.object.classes.data.freeSolo = Class.create(pimcore.object.classes.data.select, {

    type: "freeSolo",

    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: true,
        block: true,
        encryptedField: true,
    },

    initialize: function (treeNode, initData) {
        this.type = "freeSolo";
        this.initData(initData);
        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("Free Solo Select");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_select";
    },
});
