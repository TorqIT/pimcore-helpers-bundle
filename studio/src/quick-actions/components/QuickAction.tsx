import { useEffect, useRef, useState } from "react";
import { Button, Flex, Progress, theme, Tooltip, Typography } from "antd";
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
    const [isProgressing, setIsProgressing] = useState(false);
    const [progress, setProgress] = useState<number>();

    useEffect(() => {
        if (isOpen && pollProgress && getProgress) {
            getProgress()
                .then((value) => {
                    if (value !== undefined && value !== 100) {
                        setIsError(false);
                        setProgress(value);
                        setIsProgressing(true);
                    }
                })
                .catch(() => {});
        }
    }, [isOpen, pollProgress, getProgress]);

    useEffect(() => {
        let interval: ReturnType<typeof setInterval> | undefined;
        if (isProgressing && getProgress) {
            interval = setInterval(async () => {
                try {
                    const value = await getProgress();
                    if (value === undefined || value >= 100) {
                        setIsProgressing(false);
                        setProgress(undefined);
                        clearInterval(interval);
                        setIsComplete(true);
                    } else {
                        setProgress(value);
                    }
                } catch {
                    setIsError(true);
                    setIsProgressing(false);
                    setProgress(undefined);
                    clearInterval(interval);
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
        } catch {
            if (!controller.current.signal.aborted) {
                setIsError(true);
            }
        } finally {
            setIsLoading(false);
        }
    }

    async function cancel() {
        try {
            controller.current?.abort("User cancelled operation");
            setIsProgressing(false);
            setProgress(undefined);
            await onCancel?.();
        } catch {
            setIsError(true);
        }
    }

    return (
      <>
        <Flex
          gap={ 2 }
          justify={ "space-between" }
        >
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
            onClick={ !isProgressing ? call : undefined }
            style={ { width: "100%", justifyContent: "start" } }
            type={ "text" }
          >
            <Typography>{title}</Typography>
            {description && (
            <Tooltip title={ description }>
              <InfoCircleOutlined style={ { color: token.colorTextSecondary, marginLeft: 4 } } />
            </Tooltip>
                    )}
            {isError && (
            <Flex justify={ "end" }>
              <Typography style={ { color: token.colorError } }>
                Action failed, please try again.
              </Typography>
            </Flex>
                    )}
            {isProgressing && (
            <Progress
              percent={ progress }
              strokeColor={ token.colorPrimary }
            />
)}
          </Button>
          <Button
            disabled={ !isLoading && !isProgressing }
            icon={ <CloseOutlined /> }
            onClick={ cancel }
            style={ { visibility: isLoading || isProgressing ? "visible" : "hidden" } }
            type={ "text" }
          />
        </Flex>
      </>
    );
}
