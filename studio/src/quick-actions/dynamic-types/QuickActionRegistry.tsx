import { injectable } from "@pimcore/studio-ui-bundle/app";
import { DynamicTypeAbstract, DynamicTypeRegistryAbstract } from "@pimcore/studio-ui-bundle/modules/element";
import { ReactNode } from "react";

export const QUICK_ACTION_REGISTRY_SERVICE_ID = "quickActionRegistry";
export const QUICK_ACTIONS_PERMISSION = "quick_actions";

@injectable()
export abstract class AbstractQuickAction extends DynamicTypeAbstract {
    abstract readonly id: string;
    abstract readonly title: string;
    abstract readonly permission: string | null;
    abstract onClick: (signal: AbortSignal) => Promise<Response | void>;

    readonly icon?: ReactNode;
    readonly description?: string;
    readonly pollProgress?: boolean;
    getProgress?: () => Promise<number | undefined>;
    onCancel?: () => Promise<void>;
}

@injectable()
export class QuickActionRegistry extends DynamicTypeRegistryAbstract<AbstractQuickAction> {}
