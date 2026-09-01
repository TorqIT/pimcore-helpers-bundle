import { container, type IAbstractPlugin } from "@pimcore/studio-ui-bundle";
import { serviceIds } from "@pimcore/studio-ui-bundle/app";
import { componentConfig, type ComponentRegistry } from "@pimcore/studio-ui-bundle/modules/app";
import { QuickActions } from "./QuickActions";

export const QuickActionsPlugin: IAbstractPlugin = {
    name: "QuickActionsPlugin",

    onStartup({ moduleSystem }) {
        moduleSystem.registerModule({
            onInit: (): void => {
                const componentRegistry = container.get<ComponentRegistry>(
                    serviceIds["App/ComponentRegistry/ComponentRegistry"],
                );

                componentRegistry.registerToSlot(componentConfig.leftSidebar.slot.name, {
                    name: "quickActionsMenu",
                    component: QuickActions,
                    priority: 101,
                });
            },
        });
    },
};
