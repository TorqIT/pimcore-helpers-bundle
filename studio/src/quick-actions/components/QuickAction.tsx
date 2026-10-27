import { useEffect, useRef, useState } from "react";
import { Button, Flex, theme, Tooltip, Typography } from "antd";
import { CheckOutlined, CloseOutlined, ExclamationCircleOutlined, InfoCircleOutlined } from "@ant-design/icons";
import { AbstractQuickAction } from "../dynamic-types/QuickActionRegistry";

export interface QuickActionProps extends Omit<AbstractQuickAction, "id" | "permission"> {
    id?: string;
    isOpen?: boolean;
}

export function QuickAction({
    icon,
    title,
    onClick,
    description,
    isOpen,
    pollProgress,
    getProgress,
    onCancel,
}: QuickActionProps) {
    const { token } = theme.useToken();
    const [isLoading, setIsLoading] = useState(false);
    const [isComplete, setIsComplete] = useState(false);
    const [isError, setIsError] = useState(false);

    const controller = useRef<AbortController>();
    useEffect(() => () => controller.current?.abort(), []);

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
        controller.current = new AbortController();

        setIsError(false);
        setIsComplete(false);
        setIsLoading(true);

        try {
            if (!pollProgress) {
                const res = await onClick(controller.current.signal);
                if (res && !res.ok) {
                    setIsError(true);
                } else {
                    setIsComplete(true);
                }
                setIsLoading(false);
            } else {
                void onClick(controller.current.signal);
            }
        } catch (e) {
            if (!controller.current.signal.aborted) {
                setIsError(true);
            }
            setIsLoading(false);
        }
    }

    async function cancel() {
        controller.current?.abort("User cancelled operation");
        onCancel?.();
    }

    return (
        <>
            <Flex gap={2} justify={"space-between"}>
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
                    {isError && (
                        <Flex justify={"end"}>
                            <Typography style={{ color: token.colorError }}>
                                Action failed, please try again.
                            </Typography>
                        </Flex>
                    )}
                    {description && (
                        <Tooltip title={description}>
                            <InfoCircleOutlined style={{ color: token.colorTextSecondary, marginLeft: 4 }} />
                        </Tooltip>
                    )}
                </Button>
                <Button
                    type={"text"}
                    icon={<CloseOutlined />}
                    style={{ visibility: !isLoading ? "hidden" : "visible" }}
                    disabled={!isLoading}
                    onClick={cancel}
                />
            </Flex>
        </>
    );
}
