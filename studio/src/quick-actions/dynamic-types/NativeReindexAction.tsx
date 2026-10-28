import { AbstractQuickAction } from "./QuickActionRegistry";
import { injectable } from "@pimcore/studio-ui-bundle/app";
import { ReloadOutlined } from "@ant-design/icons";

export const NATIVE_REINDEX_SERVICE_ID = "nativeReindexAction";

@injectable()
export class NativeReindexAction extends AbstractQuickAction {
    id = "native-reindex";
    title = "Native Reindex";
    icon = (<ReloadOutlined />);
    description = "Native search engine reindex (reorganizes data within existing indices, no database read)";
    permission = "generic_data_index_tool";

    onClick = async (signal?: AbortSignal) => {
        await fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/native-reindex", {
            method: "POST",
            signal,
        });
    };
}
