import { ReactNode, useState } from "react";
import { Button, Flex, theme, Tooltip, Typography } from "antd";
import { CheckOutlined, ExclamationCircleOutlined, InfoCircleOutlined } from "@ant-design/icons";

export interface QuickActionListItemProps {
    icon: ReactNode;
    name: string;
    onClick: () => Promise<Response>;
    tooltip?: string;
}

export function QuickActionListItem({ icon, name, onClick, tooltip }: QuickActionListItemProps) {
    const { token } = theme.useToken();
    const [isLoading, setIsLoading] = useState(false);
    const [isComplete, setIsComplete] = useState(false);
    const [isError, setIsError] = useState(false);

    async function call() {
        setIsError(false);
        setIsComplete(false);
        setIsLoading(true);
        const res = await onClick();
        if (res.ok) {
            setIsComplete(true);
        } else {
            setIsError(true);
        }
        setIsLoading(false);
    }

    return (
      <>
        <Button
          icon={
                    isError ? (
                      <ExclamationCircleOutlined style={ { color: token.colorError } } />
                    ) : isComplete ? (
                      <CheckOutlined style={ { color: token.colorSuccess } } />
                    ) : (
                        icon
                    )
                }
          loading={ isLoading }
          onClick={ call }
          style={ { width: "100%", justifyContent: "start" } }
          type={ "text" }
        >
          <Typography>{name}</Typography>
          {tooltip && (
            <Tooltip title={ tooltip }>
              <InfoCircleOutlined style={ { color: token.colorTextSecondary, marginLeft: 4 } } />
            </Tooltip>
                )}
          {isError && (
            <Flex justify={ "end" }>
              <Typography style={ { color: token.colorError } }>Action failed, please try again.</Typography>
            </Flex>
                )}
        </Button>
      </>
    );
}
