import { ThunderboltOutlined } from "@ant-design/icons";
import { Button } from "antd";
import { isAllowed } from "@pimcore/studio-ui-bundle/modules/auth";
import { QUICK_ACTIONS_PERMISSION } from "../dynamic-types/QuickActionRegistry";
import { useQuickActionsModal } from "../hooks/useQuickActionsModal";

export const QUICK_ACTIONS_SIDEBAR_BUTTON_NAME = "quickActionsMenu";

export function QuickActionsSidebarButton() {
    const { open } = useQuickActionsModal();

    return isAllowed(QUICK_ACTIONS_PERMISSION) ? (
      <Button
        icon={ <ThunderboltOutlined /> }
        onClick={ open }
        type={ "text" }
      />
    ) : null;
}
