pimcore.registerNS("pimcore.object.tags.freeSolo");

pimcore.object.tags.freeSolo = Class.create(pimcore.object.tags.select, {

    type: "freeSolo",

    getLayoutEdit: function () {
        const storeData = [];
        if (!this.fieldConfig.mandatory) {
            storeData.push({ value: '', key: '(' + t('empty') + ')' });
        }

        if (this.fieldConfig.options) {
            for (let i = 0; i < this.fieldConfig.options.length; i++) {
                let label = t(this.fieldConfig.options[i].key);
                if (label.indexOf('<') >= 0) {
                    label = replace_html_event_attributes(strip_tags(label, "div,span,b,strong,em,i,small,sup,sub2"));
                }
                storeData.push({ value: this.fieldConfig.options[i].value, key: label });
            }
        }

        const store = Ext.create('Ext.data.Store', {
            fields: ['value', 'key'],
            data: storeData,
        });

        const options = {
            name: this.fieldConfig.name,
            triggerAction: 'all',
            editable: true,
            queryMode: 'local',
            anyMatch: true,
            autoComplete: false,
            forceSelection: false,
            selectOnFocus: true,
            typeAhead: true,
            fieldLabel: t(this.fieldConfig.title),
            store,
            componentCls: this.getWrapperClassNames(),
            width: 250,
            tpl: Ext.create('Ext.XTemplate',
                '<ul class="x-list-plain"><tpl for=".">',
                '<li role="option" class="x-boundlist-item">{key}</li>',
                '</tpl></ul>'
            ),
            displayTpl: Ext.create('Ext.XTemplate',
                '<tpl for=".">',
                '{[Ext.util.Format.stripTags(values.key)]}',
                '</tpl>'
            ),
            displayField: 'key',
            valueField: 'value',
            labelWidth: 100,
            value: typeof this.data === 'string' || typeof this.data === 'number' ? this.data : '',
        };

        if (this.fieldConfig.labelWidth) {
            options.labelWidth = this.fieldConfig.labelWidth;
        }

        if (this.fieldConfig.width) {
            options.width = this.fieldConfig.width;
        }

        if (this.fieldConfig.labelAlign) {
            options.labelAlign = this.fieldConfig.labelAlign;
        }

        if (!this.fieldConfig.labelAlign || this.fieldConfig.labelAlign === 'left') {
            options.width = this.sumWidths(options.width, options.labelWidth);
        }

        this.component = new Ext.form.ComboBox(options);

        return this.component;
    },
});
