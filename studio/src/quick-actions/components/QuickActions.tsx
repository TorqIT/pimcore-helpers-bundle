import { useState } from "react";
import { ThunderboltOutlined } from "@ant-design/icons";
import { Button, Flex, Modal, Pagination, theme, Typography } from "antd";
import { QuickAction } from "./QuickAction";
import { isAllowed } from "@pimcore/studio-ui-bundle/modules/auth";
import { QUICK_ACTION_REGISTRY_SERVICE_ID, QuickActionRegistry } from "../dynamic-types/QuickActionRegistry";
import { container } from "@pimcore/studio-ui-bundle";

const PERMISSION = "quick_actions";
const PAGE_SIZE = 10;

export function QuickActions() {
    const { token } = theme.useToken();
    const [isOpen, setIsOpen] = useState(false);
    const [page, setPage] = useState(1);

    const registry = container.get<QuickActionRegistry>(QUICK_ACTION_REGISTRY_SERVICE_ID);
    const actions = registry.getDynamicTypes().filter(({ permission }) => !permission || isAllowed(permission));
    const isOnPage = (index: number) => Math.floor(index / PAGE_SIZE) + 1 === page;

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
                    {actions.map(({ id, ...action }, index) => (
                        <div key={id} hidden={!isOnPage(index)}>
                            <QuickAction {...action} isOpen={isOpen} />
                        </div>
                    ))}
                </Flex>
                {actions.length > PAGE_SIZE && (
                    <Flex justify={"center"} style={{ marginTop: token.marginSM }}>
                        <Pagination
                            current={page}
                            onChange={setPage}
                            pageSize={PAGE_SIZE}
                            showSizeChanger={false}
                            showQuickJumper={false}
                            total={actions.length}
                        />
                    </Flex>
                )}
            </Modal>
        </>
    ) : null;
}
