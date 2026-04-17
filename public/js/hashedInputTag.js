pimcore.registerNS("pimcore.object.tags.hashedInput");

pimcore.object.tags.hashedInput = Class.create(pimcore.object.tags.input, {

    type: "hashedInput",
    dirty: false,

    initialize: function (data, fieldConfig) {
        this.dirty = false;
        // Parent initializes this.data and this.fieldConfig
        pimcore.object.tags.input.prototype.initialize.call(this, data, fieldConfig);
    },

    getLayoutEdit: function ($super) {
        const layout = $super();

        if (this.component) {
            this.component.on("change", function () {
                this.dirty = true;
            }.bind(this));
        }

        return layout;
    },

    // Submit empty string when the field is unchanged so the backend preserves
    // the existing hash (see HashedInput::getDataFromEditmode).
    getValue: function ($super) {
        if (!this.dirty) {
            return "";
        }
        return $super();
    }
});
