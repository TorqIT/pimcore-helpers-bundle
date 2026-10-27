import { useEffect, useState } from "react";
import { Button, Flex, theme, Tooltip, Typography } from "antd";
import { CheckOutlined, ExclamationCircleOutlined, InfoCircleOutlined } from "@ant-design/icons";
import { AbstractQuickAction } from "../dynamic-types/QuickActionRegistry";

export interface QuickActionProps extends Omit<AbstractQuickAction, "id" | "permission"> {
    id?: string;
    isOpen?: boolean;
}

export function QuickAction({ icon, title, onClick, description, isOpen }: QuickActionProps) {
    const { token } = theme.useToken();
    const [isLoading, setIsLoading] = useState(false);
    const [isComplete, setIsComplete] = useState(false);
    const [isError, setIsError] = useState(false);

    useEffect(() => {
        let timer: ReturnType<typeof setTimeout> | undefined;
        if (isComplete && isOpen) {
            timer = setTimeout(() => setIsComplete(false), 3000);
        }

        return () => {
            clearTimeout(timer);
        };
    }, [isComplete, isOpen]);

    async function call() {
        setIsError(false);
        setIsComplete(false);
        setIsLoading(true);

        try {
            const res = await onClick();
            if (res && !res.ok) {
                setIsError(true);
            } else {
                setIsComplete(true);
            }
        } catch (e) {
            setIsError(true);
        } finally {
            setIsLoading(false);
        }
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
