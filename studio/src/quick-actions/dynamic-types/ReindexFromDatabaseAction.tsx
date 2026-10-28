import { AbstractQuickAction } from "./QuickActionRegistry";
import { injectable } from "@pimcore/studio-ui-bundle/app";
import { FileSearchOutlined } from "@ant-design/icons";

export const REINDEX_FROM_DATABASE_SERVICE_ID = "reindexFromDatabaseAction";

@injectable()
export class ReindexFromDatabaseAction extends AbstractQuickAction {
    id = "reindex-from-database";
    title = "Reindex from Database";
    icon = (<FileSearchOutlined />);
    description = "Update index mappings and queue all elements for reindex from the database";
    permission = "generic_data_index_tool";

    onClick = async (signal?: AbortSignal) => {
        await fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/reindex", {
            method: "POST",
            signal,
        });
    };
}
