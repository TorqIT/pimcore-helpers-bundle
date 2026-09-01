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
        <Button
          icon={ <ThunderboltOutlined /> }
          onClick={ () => setIsOpen(true) }
          type={ "text" }
        />
        <Modal
          destroyOnClose
          footer={ [
            <Button
              key={ "back" }
              onClick={ () => setIsOpen(false) }
            >
              Close
            </Button>,
                ] }
          onCancel={ () => setIsOpen(false) }
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
            <QuickActionListItem
              icon={ <ReloadOutlined /> }
              name={ "Native Reindex" }
              onClick={ () =>
                            fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/native-reindex", { method: "POST" })
                        }
              tooltip={
                            "Native search engine reindex (reorganizes data within existing indices, no database read)"
                        }
            />
            <QuickActionListItem
              icon={ <FileSearchOutlined /> }
              name={ "Reindex from Database" }
              onClick={ () => fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/reindex", { method: "POST" }) }
              tooltip={ "Update index mappings and queue all elements for reindex from the database" }
            />
            <QuickActionListItem
              icon={ <FileSyncOutlined /> }
              name={ "Recreate Indices" }
              onClick={ () => fetch("/pimcore-studio/api/pimcore-helpers/generic-data-index/recreate", { method: "POST" }) }
              tooltip={ "Delete and recreate indices, then queue all elements" }
            />
          </Flex>
        </Modal>
      </>
    ) : null;
}
