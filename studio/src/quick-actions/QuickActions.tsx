import { useState } from "react";
import { FileSearchOutlined, FileSyncOutlined, ThunderboltOutlined, ReloadOutlined } from "@ant-design/icons";
import { Button, Flex, Modal, Typography, theme } from "antd";
import { QuickActionListItem } from "./QuickActionListItem";
import { isAllowed } from "@pimcore/studio-ui-bundle/modules/auth";

const PERMISSION = "generic_data_index_tool";

export function QuickActions() {
    const { token } = theme.useToken();
    const [isOpen, setIsOpen] = useState(false);

    return isAllowed(PERMISSION) ? (
        <>
            <Button icon={<ThunderboltOutlined />} onClick={() => setIsOpen(true)} type={"text"} />
            <Modal
                open={isOpen}
                onCancel={() => setIsOpen(false)}
                destroyOnClose
                footer={[
                    <Button key={"back"} onClick={() => setIsOpen(false)}>
                        Close
                    </Button>,
                ]}
                title={
                    <Typography style={{ color: token.colorPrimary }}>
                        <ThunderboltOutlined /> Quick Actions
                    </Typography>
                }
            >
                <Flex vertical gap={2}>
                    <QuickActionListItem
                        icon={<ReloadOutlined />}
                        name={"Native Reindex"}
                        tooltip={
                            "Native search engine reindex (reorganizes data within existing indices, no database read)"
                        }
                        onClick={() =>
                            fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/native-reindex", { method: "POST" })
                        }
                    />
                    <QuickActionListItem
                        icon={<FileSearchOutlined />}
                        name={"Reindex from Database"}
                        tooltip={"Update index mappings and queue all elements for reindex from the database"}
                        onClick={() => fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/reindex", { method: "POST" })}
                    />
                    <QuickActionListItem
                        icon={<FileSyncOutlined />}
                        name={"Recreate Indices"}
                        tooltip={"Delete and recreate indices, then queue all elements"}
                        onClick={() => fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/recreate", { method: "POST" })}
                    />
                </Flex>
            </Modal>
        </>
    ) : null;
}
