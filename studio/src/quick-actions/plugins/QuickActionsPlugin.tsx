import { container, type IAbstractPlugin } from "@pimcore/studio-ui-bundle";
import { serviceIds } from "@pimcore/studio-ui-bundle/app";
import { componentConfig, type ComponentRegistry } from "@pimcore/studio-ui-bundle/modules/app";
import { QUICK_ACTIONS_SIDEBAR_BUTTON_NAME, QuickActionsSidebarButton } from "../components/QuickActionsSidebarButton";
import { QUICK_ACTION_REGISTRY_SERVICE_ID, QuickActionRegistry } from "../dynamic-types/QuickActionRegistry";
import { NATIVE_REINDEX_SERVICE_ID, NativeReindexAction } from "../dynamic-types/NativeReindexAction";
import {
    REINDEX_FROM_DATABASE_SERVICE_ID,
    ReindexFromDatabaseAction,
} from "../dynamic-types/ReindexFromDatabaseAction";
import { RECREATE_INDICES_SERVICE_ID, RecreateIndicesAction } from "../dynamic-types/RecreateIndicesAction";

export const QuickActionsPlugin: IAbstractPlugin = {
    name: "QuickActionsPlugin",

    onInit({ container }) {
        container.bind(QUICK_ACTION_REGISTRY_SERVICE_ID).to(QuickActionRegistry).inSingletonScope();
        container.bind(NATIVE_REINDEX_SERVICE_ID).to(NativeReindexAction).inSingletonScope();
        container.bind(REINDEX_FROM_DATABASE_SERVICE_ID).to(ReindexFromDatabaseAction).inSingletonScope();
        container.bind(RECREATE_INDICES_SERVICE_ID).to(RecreateIndicesAction).inSingletonScope();

        const registry = container.get<QuickActionRegistry>(QUICK_ACTION_REGISTRY_SERVICE_ID);
        registry.registerDynamicType(container.get(NATIVE_REINDEX_SERVICE_ID));
        registry.registerDynamicType(container.get(REINDEX_FROM_DATABASE_SERVICE_ID));
        registry.registerDynamicType(container.get(RECREATE_INDICES_SERVICE_ID));
    },

    onStartup({ moduleSystem }) {
        moduleSystem.registerModule({
            onInit: () => {
                const componentRegistry = container.get<ComponentRegistry>(
                    serviceIds["App/ComponentRegistry/ComponentRegistry"],
                );

                componentRegistry.registerToSlot(componentConfig.leftSidebar.slot.name, {
                    name: QUICK_ACTIONS_SIDEBAR_BUTTON_NAME,
                    component: QuickActionsSidebarButton,
                    priority: 101,
                });
            },
        });
    },
};
