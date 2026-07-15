/**
 * Adds a reusable "file" environment field type to the Pimcore Copilot
 * automation-action run form (classic ExtJS admin).
 *
 * Copilot's own renderer (vendor: PimcoreCopilotBundle .../parts/environmentVariableForm.js)
 * is a switch over field_type with no `default` case and no extension hook, so an unknown
 * `file` type would render nothing. Rather than copy that vendor method verbatim (brittle on
 * upgrades), we WRAP it: call the original to build the panel for all built-in field types,
 * then insert our own file-upload field for every env var whose field_type === 'file'.
 * Inserting at each file field's original index (ascending) reproduces the authored order.
 *
 * The file field uploads the chosen file immediately to the bundle's temp-upload endpoint and
 * stores the returned temp asset path in a hidden field named after the env var, so it
 * round-trips through the form's getValues() exactly like any other field. See
 * Torq\PimcoreHelpersBundle\Controller\CopilotFileUploadController and
 * Torq\PimcoreHelpersBundle\Copilot\AutomationAction\Environment\Type\FileUpload.
 */
(function () {
    'use strict';

    const ns = pimcore
        && pimcore.plugin
        && pimcore.plugin.PimcoreCopilotBundle
        && pimcore.plugin.PimcoreCopilotBundle.copilot
        && pimcore.plugin.PimcoreCopilotBundle.copilot.adapters
        && pimcore.plugin.PimcoreCopilotBundle.copilot.adapters.parts
        && pimcore.plugin.PimcoreCopilotBundle.copilot.adapters.parts.environmentVariableForm;

    // Copilot bundle not installed, or its renderer moved/changed — do nothing.
    if (!ns || !ns.prototype || typeof ns.prototype.buildEnvironmentVariableForm !== 'function') {
        return;
    }

    const buildFileField = (variableConfig) => {
        const configuration = variableConfig.configuration || {};
        const required = configuration.required === true;
        const uploadFolder = configuration.upload_folder || null;
        const allowedExtensions = Array.isArray(configuration.allowed_extensions)
            ? configuration.allowed_extensions
            : [];

        const hiddenField = Ext.create('Ext.form.field.Hidden', {
            name: variableConfig.name,
            allowBlank: !required,
        });

        const statusField = Ext.create('Ext.form.field.Display', {
            hideLabel: true,
            style: 'margin: -8px 0 0 105px;',
        });

        const fileField = Ext.create('Ext.form.field.File', {
            fieldLabel: Ext.htmlEncode(variableConfig.name),
            labelWidth: 100,
            allowBlank: true,
            buttonText: '',
            buttonConfig: {
                iconCls: 'pimcore_icon_upload',
            },
            listeners: {
                change: function (field) {
                    const inputDom = field.fileInputEl ? field.fileInputEl.dom : null;
                    if (!inputDom || !inputDom.files || inputDom.files.length === 0) {
                        return;
                    }

                    const file = inputDom.files[0];

                    if (allowedExtensions.length) {
                        const extension = (file.name.split('.').pop() || '').toLowerCase();
                        if (allowedExtensions.indexOf(extension) === -1) {
                            hiddenField.setValue('');
                            statusField.setValue(`<span style="color:#c0392b;">${t('error')}: .${Ext.htmlEncode(extension)} (${Ext.htmlEncode(allowedExtensions.join(', '))})</span>`);
                            field.reset();
                            return;
                        }
                    }

                    const formData = new FormData();
                    formData.append('file', file);

                    let url = Routing.generate('torq_helpers_copilot_file_upload');
                    if (uploadFolder) {
                        url += `${url.indexOf('?') === -1 ? '?' : '&'}folder=${encodeURIComponent(uploadFolder)}`;
                    }

                    field.setDisabled(true);
                    statusField.setValue(`${t('please_wait')}…`);

                    fetch(url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'X-pimcore-csrf-token': pimcore.settings['csrfToken'],
                        },
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data && data.success) {
                                hiddenField.setValue(data.assetPath);
                                statusField.setValue(`<span style="color:#27ae60;">${Ext.htmlEncode(data.filename)}</span>`);
                            } else {
                                hiddenField.setValue('');
                                statusField.setValue(`<span style="color:#c0392b;">${Ext.htmlEncode((data && data.message) || t('error'))}</span>`);
                            }
                        })
                        .catch((error) => {
                            hiddenField.setValue('');
                            statusField.setValue(`<span style="color:#c0392b;">${Ext.htmlEncode(String(error))}</span>`);
                        })
                        .finally(() => {
                            field.setDisabled(false);
                        });
                },
            },
        });

        return Ext.create('Ext.form.FieldContainer', {
            layout: 'anchor',
            items: [fileField, statusField, hiddenField],
        });
    };

    const original = ns.prototype.buildEnvironmentVariableForm;

    ns.prototype.buildEnvironmentVariableForm = function (jsConfig) {
        const panel = original.apply(this, arguments);

        if (!panel || typeof panel.insert !== 'function'
            || !jsConfig || !Array.isArray(jsConfig.environment_variables)) {
            return panel;
        }

        jsConfig.environment_variables.forEach((variableConfig, index) => {
            if (variableConfig && variableConfig.field_type === 'file') {
                panel.insert(index, buildFileField(variableConfig));
            }
        });

        return panel;
    };
})();
