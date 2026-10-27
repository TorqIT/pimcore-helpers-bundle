import { useState } from "react";
import { ThunderboltOutlined } from "@ant-design/icons";
import { Button, Flex, Modal, theme, Typography } from "antd";
import { QuickAction } from "./QuickAction";
import { isAllowed } from "@pimcore/studio-ui-bundle/modules/auth";
import { QUICK_ACTION_REGISTRY_SERVICE_ID, QuickActionRegistry } from "../dynamic-types/QuickActionRegistry";
import { container } from "@pimcore/studio-ui-bundle";

const PERMISSION = "quick_actions";

export function QuickActions() {
    const { token } = theme.useToken();
    const [isOpen, setIsOpen] = useState(false);

    const registry = container.get<QuickActionRegistry>(QUICK_ACTION_REGISTRY_SERVICE_ID);
    const actions = registry.getDynamicTypes().filter(({ permission }) => !permission || isAllowed(permission));

    return isAllowed(PERMISSION) ? (
        <>
            <Button icon={<ThunderboltOutlined />} onClick={() => setIsOpen(true)} type={"text"} />
            <Modal
                forceRender // skip useMemo check on children so reactive to isOpen
                footer={[
                    <Button key={"back"} onClick={() => setIsOpen(false)}>
                        Close
                    </Button>,
                ]}
                onCancel={() => setIsOpen(false)}
                open={isOpen}
                title={
                    <Typography style={{ color: token.colorPrimary }}>
                        <ThunderboltOutlined /> Quick Actions
                    </Typography>
                }
            >
                <Flex gap={2} vertical>
                    {actions.map(({ id, title, icon, description, onClick, getProgress }) => (
                        <QuickAction
                            key={id}
                            title={title}
                            icon={icon}
                            description={description}
                            onClick={onClick}
                            getProgress={getProgress}
                            isOpen={isOpen}
                        />
                    ))}
                </Flex>
            </Modal>
        </>
    ) : null;
}
