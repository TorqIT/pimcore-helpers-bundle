import { useState } from "react";
import { ThunderboltOutlined } from "@ant-design/icons";
import { Button, Flex, Modal, Pagination, theme, Typography } from "antd";
import { QuickAction } from "./QuickAction";
import { isAllowed } from "@pimcore/studio-ui-bundle/modules/auth";
import {
    QUICK_ACTION_REGISTRY_SERVICE_ID,
    QUICK_ACTIONS_PERMISSION,
    QuickActionRegistry,
} from "../dynamic-types/QuickActionRegistry";
import { container } from "@pimcore/studio-ui-bundle";

const PAGE_SIZE = 10;

interface QuickActionsModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export function QuickActionsModal({ isOpen, onClose }: QuickActionsModalProps) {
    const { token } = theme.useToken();
    const [page, setPage] = useState(1);

    const registry = container.get<QuickActionRegistry>(QUICK_ACTION_REGISTRY_SERVICE_ID);
    const actions = registry.getDynamicTypes().filter(({ permission }) => !permission || isAllowed(permission));
    const isOnPage = (index: number) => Math.floor(index / PAGE_SIZE) + 1 === page;

    return isAllowed(QUICK_ACTIONS_PERMISSION) ? (
      <Modal
        footer={ [
          <Button
            key={ "back" }
            onClick={ onClose }
          >
            Close
          </Button>,
            ] }
        forceRender // skip useMemo check on children so reactive to isOpen
        onCancel={ onClose }
        open={ isOpen }
        title={
          <Typography style={ { color: token.colorPrimary } }>
            <ThunderboltOutlined /> Quick Actions
          </Typography>
            }
      >
        <Flex
          gap={ 2 }
          vertical
        >
          {actions.map(({ id, ...action }, index) => (
            <div
              hidden={ !isOnPage(index) }
              key={ id }
            >
              <QuickAction
                { ...action }
                isOpen={ isOpen }
              />
            </div>
                ))}
        </Flex>
        {actions.length > PAGE_SIZE && (
          <Flex
            justify={ "center" }
            style={ { marginTop: token.marginSM } }
          >
            <Pagination
              current={ page }
              onChange={ setPage }
              pageSize={ PAGE_SIZE }
              showQuickJumper={ false }
              showSizeChanger={ false }
              total={ actions.length }
            />
          </Flex>
            )}
      </Modal>
    ) : null;
}
