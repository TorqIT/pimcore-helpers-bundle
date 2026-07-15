<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Copilot\AutomationAction\Environment\Type;

use Pimcore\Bundle\CopilotBundle\AutomationAction\Configuration\Environment\AbstractEnvironment;

/**
 * Reusable Copilot automation-action environment field type: a file upload.
 *
 * In the classic ExtJS admin this renders (via public/js/copilotEnvironmentFileUpload.js)
 * as a file picker. The chosen file is uploaded immediately to
 * {@see \Torq\PimcoreHelpersBundle\Controller\CopilotFileUploadController}, staged as a
 * temporary Pimcore asset, and that asset's path is stored as this field's value. The
 * automation-action message handler then reads the path from the job's environment data.
 *
 * Reference it from an automation action's YAML by FQCN:
 *
 *     environment_variables:
 *         -
 *             configuration:
 *                 required: false
 *                 allowed_extensions: [csv]              # optional client-side extension filter
 *                 upload_folder: /protected/_uploads     # optional asset folder to stage into
 *             name: 'Source File'
 *             type: Torq\PimcoreHelpersBundle\Copilot\AutomationAction\Environment\Type\FileUpload
 *             field_type: file
 *
 * Registered by FQCN only (no service tag), so Copilot's EnvironmentProvider instantiates it
 * directly and it never surfaces in the authoring picker (which would require an inline-help
 * template keyed on the type). Any Copilot job can reuse it.
 */
class FileUpload extends AbstractEnvironment
{
    public function getType(): string
    {
        return 'file';
    }

    public function getLabel(): string
    {
        return 'torq_helpers_copilot_environment_type_file';
    }

    public function getIcon(): string
    {
        return 'pimcore_icon_upload';
    }

    protected function configureEnvironment(): void
    {
        $this->environmentConfiguration->setDefault('required', false);
        $this->environmentConfiguration->setAllowedTypes('required', 'bool');

        // Optional whitelist of accepted extensions (lower-case, no dot). Empty = allow any.
        $this->environmentConfiguration->setDefault('allowed_extensions', []);
        $this->environmentConfiguration->setAllowedTypes('allowed_extensions', 'string[]');

        // Optional Pimcore asset folder path to stage uploads into. Null = controller default.
        $this->environmentConfiguration->setDefault('upload_folder', null);
        $this->environmentConfiguration->setAllowedTypes('upload_folder', ['null', 'string']);
    }
}
