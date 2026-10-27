import { injectable } from "@pimcore/studio-ui-bundle/app";
import { DynamicTypeAbstract, DynamicTypeRegistryAbstract } from "@pimcore/studio-ui-bundle/modules/element";
import { ReactNode } from "react";

export const QUICK_ACTION_REGISTRY_SERVICE_ID = "quickActionRegistry";

@injectable()
export abstract class AbstractQuickAction extends DynamicTypeAbstract {
    abstract readonly id: string;
    abstract readonly title: string;
    abstract onClick(): Promise<Response | void>;
    abstract readonly permission: string | null;

    readonly icon?: ReactNode;
    readonly description?: string;
    async getProgress(): Promise<number | void> {}
}

@injectable()
export class QuickActionRegistry extends DynamicTypeRegistryAbstract<AbstractQuickAction> {}
