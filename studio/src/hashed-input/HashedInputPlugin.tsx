import { container, type IAbstractPlugin } from "@pimcore/studio-ui-bundle";
import { HashedInputObjectData } from "./HashedInputObjectData";
import { serviceIds } from "@pimcore/studio-ui-bundle/app";
import { HashedinputFieldDefinition } from "./HashedinputFieldDefinition";
import { DynamicTypeObjectDataRegistry } from "@pimcore/studio-ui-bundle/modules/element";
import { DynamicTypeFieldDefinitionRegistry } from "@pimcore/studio-ui-bundle/modules/field-definitions";
import { IconColorGroupsRegistry } from "@pimcore/studio-ui-bundle/components";

const HASHED_INPUT_SERVICE_ID = "PimcoreHelpersBundle/DynamicTypes/ObjectData/HashedInput";
const HASHED_INPUT_DEFINITION_SERVICE_ID = "PimcoreHelpersBundle/DynamicTypes/FieldDefinition/HashedInput";

export const HashedInputPlugin: IAbstractPlugin = {
    name: "HashedInputPlugin",

    onInit: ({ container }): void => {
        container.bind(HASHED_INPUT_SERVICE_ID).to(HashedInputObjectData).inSingletonScope();
        container.bind(HASHED_INPUT_DEFINITION_SERVICE_ID).to(HashedinputFieldDefinition).inSingletonScope();
    },

    onStartup: ({ moduleSystem }): void => {
        moduleSystem.registerModule({
            onInit: (): void => {
                const colorGroupRegistry = container.get<IconColorGroupsRegistry>(serviceIds.iconColorGroupsRegistry);
                colorGroupRegistry.addToGroup("fieldDefinition", "lock", "colorCodingMint2");

                const objectDataRegistry = container.get<DynamicTypeObjectDataRegistry>(
                    serviceIds["DynamicTypes/ObjectDataRegistry"],
                );
                objectDataRegistry.registerDynamicType(container.get(HASHED_INPUT_SERVICE_ID));

                const fieldDefinitionRegistry = container.get<DynamicTypeFieldDefinitionRegistry>(
                    serviceIds["DynamicTypes/FieldDefinitionRegistry"],
                );
                fieldDefinitionRegistry.registerDynamicType(container.get(HASHED_INPUT_DEFINITION_SERVICE_ID));
            },
        });
        console.log("HashedInputPlugin starting...");
    },
};
