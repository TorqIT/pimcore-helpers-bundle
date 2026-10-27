import { useEffect, useRef, useState } from "react";
import { Button, Flex, Progress, theme, Tooltip, Typography } from "antd";
import { CheckOutlined, CloseOutlined, ExclamationCircleOutlined, InfoCircleOutlined } from "@ant-design/icons";
import { AbstractQuickAction } from "../dynamic-types/QuickActionRegistry";
import { Progressbar } from "@pimcore/studio-ui-bundle/components";

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
    const [isProgressing, setIsProgressing] = useState(false);
    const [progress, setProgress] = useState<number>();

    useEffect(() => {
        if (pollProgress && getProgress) {
            getProgress().then((value) => {
                if (value > 0 && value != 100) {
                    setIsProgressing(true);
                }
            });
        }
    }, [pollProgress, getProgress]);

    useEffect(() => {
        let interval: ReturnType<typeof setInterval> | undefined;
        if (isProgressing && getProgress) {
            interval = setInterval(async () => {
                const value = await getProgress();
                if (value >= 100) {
                    setIsProgressing(false);
                    setProgress(undefined);
                    setIsComplete(true);
                    clearInterval(interval);
                } else {
                    setProgress(value);
                }
            }, 5000);
        }

        return () => clearInterval(interval);
    }, [isProgressing, getProgress]);

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
        setIsProgressing(false);
        setProgress(undefined);
        setIsLoading(true);

        try {
            const res = await onClick(controller.current.signal);
            if (res && !res.ok) {
                setIsError(true);
            } else {
                if (pollProgress) {
                    setIsProgressing(true);
                } else {
                    setIsComplete(true);
                }
            }
        } catch (e) {
            if (!controller.current.signal.aborted) {
                setIsError(true);
            }
        } finally {
            setIsLoading(false);
        }
    }

    async function cancel() {
        controller.current?.abort("User cancelled operation");
        setIsProgressing(false);
        setProgress(undefined);
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
                    {isProgressing && <Progress percent={progress} strokeColor={token.colorPrimary} />}
                    {description && (
                        <Tooltip title={description}>
                            <InfoCircleOutlined style={{ color: token.colorTextSecondary, marginLeft: 4 }} />
                        </Tooltip>
                    )}
                </Button>
                <Button
                    type={"text"}
                    icon={<CloseOutlined />}
                    style={{ visibility: isLoading || isProgressing ? "visible" : "hidden" }}
                    disabled={!isLoading && !isProgressing}
                    onClick={cancel}
                />
            </Flex>
        </>
    );
}
