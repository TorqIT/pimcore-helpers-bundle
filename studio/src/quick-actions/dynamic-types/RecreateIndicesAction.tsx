import { AbstractQuickAction } from "./QuickActionRegistry";
import { injectable } from "@pimcore/studio-ui-bundle/app";
import { FileSyncOutlined } from "@ant-design/icons";

export const RECREATE_INDICES_SERVICE_ID = "recreateIndicesAction";

@injectable()
export class RecreateIndicesAction extends AbstractQuickAction {
    id = "recreate-indices";
    title = "Recreate Indices";
    icon = (<FileSyncOutlined />);
    description = "Delete and recreate indices, then queue all elements";
    permission = "generic_data_index_tool";

    onClick = async (signal?: AbortSignal) => {
        await fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/recreate", {
            method: "POST",
            signal,
        });
    };
}
