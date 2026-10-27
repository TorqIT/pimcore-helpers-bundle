import { useState } from "react";
import { Button, Flex, theme, Tooltip, Typography } from "antd";
import { CheckOutlined, ExclamationCircleOutlined, InfoCircleOutlined } from "@ant-design/icons";
import { AbstractQuickAction } from "./QuickActionRegistry";

export interface QuickActionProps extends Omit<AbstractQuickAction, "id" | "permission"> {
    id?: string;
}

export function QuickAction({ icon, title, onClick, description }: QuickActionProps) {
    const { token } = theme.useToken();
    const [isLoading, setIsLoading] = useState(false);
    const [isComplete, setIsComplete] = useState(false);
    const [isError, setIsError] = useState(false);

    async function call() {
        setIsError(false);
        setIsComplete(false);
        setIsLoading(true);
        const res = await onClick();
        if (res) {
            if (res.ok) {
                setIsComplete(true);
            } else {
                setIsError(true);
            }
        }
        setIsLoading(false);
    }

    return (
        <>
            <Button
                icon={
                    isError ? (
                        <ExclamationCircleOutlined style={{ color: token.colorError }} />
                    ) : isComplete ? (
                        <CheckOutlined style={{ color: token.colorSuccess }} />
                    ) : (
                        icon
                    )
                }
                loading={isLoading}
                onClick={call}
                style={{ width: "100%", justifyContent: "start" }}
                type={"text"}
            >
                <Typography>{title}</Typography>
                {description && (
                    <Tooltip title={description}>
                        <InfoCircleOutlined style={{ color: token.colorTextSecondary, marginLeft: 4 }} />
                    </Tooltip>
                )}
                {isError && (
                    <Flex justify={"end"}>
                        <Typography style={{ color: token.colorError }}>Action failed, please try again.</Typography>
                    </Flex>
                )}
            </Button>
        </>
    );
}
