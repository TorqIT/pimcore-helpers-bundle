import { container, type AbstractModule } from "@pimcore/studio-ui-bundle";
import { type IAbstractPlugin } from "@pimcore/studio-ui-bundle";
import { HashedInputObjectData } from "./HashedInputObjectData";
import { serviceIds } from "@pimcore/studio-ui-bundle/app";
import { HashedinputFieldDefinition } from "./HashedinputFieldDefinition";
import { DynamicTypeObjectDataRegistry } from "@pimcore/studio-ui-bundle/modules/element";
import { DynamicTypeFieldDefinitionRegistry } from "@pimcore/studio-ui-bundle/modules/field-definitions";

const HASHED_INPUT_SERVICE_ID = "PimcoreHelpersBundle/DynamicTypes/ObjectData/HashedInput";
const HASHED_INPUT_DEFINITION_SERVICE_ID = "PimcoreHelpersBundle/DynamicTypes/FieldDefinition/HashedInput";

const HashedInputModule: AbstractModule = {
    onInit: (): void => {
        const objectDataRegistry = container.get<DynamicTypeObjectDataRegistry>(
            serviceIds["DynamicTypes/ObjectDataRegistry"],
        );
        objectDataRegistry.registerDynamicType(container.get(HASHED_INPUT_SERVICE_ID));

        const fieldDefinitionRegistry = container.get<DynamicTypeFieldDefinitionRegistry>(
            serviceIds["DynamicTypes/FieldDefinitionRegistry"],
        );
        fieldDefinitionRegistry.registerDynamicType(container.get(HASHED_INPUT_DEFINITION_SERVICE_ID));
    },
};

export const HashedInputPlugin: IAbstractPlugin = {
    name: "HashedInputPlugin",

    onInit: ({ container }): void => {
        container.bind(HASHED_INPUT_SERVICE_ID).to(HashedInputObjectData).inSingletonScope();
        container.bind(HASHED_INPUT_DEFINITION_SERVICE_ID).to(HashedinputFieldDefinition).inSingletonScope();
    },

    onStartup: ({ moduleSystem }): void => {
        moduleSystem.registerModule(HashedInputModule);
        console.log("HashedInputPlugin starting...");
    },
};
