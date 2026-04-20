import { container, type IAbstractPlugin } from "@pimcore/studio-ui-bundle";
import { ArrayFieldObjectData } from "./ArrayFieldObjectData";
import { DynamicTypeObjectDataRegistry } from "@pimcore/studio-ui-bundle/modules/element";
import { serviceIds } from "@pimcore/studio-ui-bundle/app";
import { ArrayFieldGridCell } from "./ArrayFieldGridCell";
import { ArrayFieldDefinition } from "./ArrayFieldDefinition";
import { DynamicTypeFieldDefinitionRegistry } from "@pimcore/studio-ui-bundle/modules/field-definitions";
import { IconColorGroupsRegistry } from "@pimcore/studio-ui-bundle/components";

const ARRAY_FIELD_SERVICE_ID = "PimcoreHelpersBundle/DynamicTypes/ObjectData/ArrayField";
const ARRAY_FIELD_DEFINITION_SERVICE_ID = "PimcoreHelpersBundle/DynamicTypes/FieldDefinition/ArrayField";

export const ArrayFieldPlugin: IAbstractPlugin = {
    name: "ArrayFieldPlugin",

    onInit: ({ container }): void => {
        container.bind(ARRAY_FIELD_SERVICE_ID).to(ArrayFieldObjectData).inSingletonScope();
        container.bind(ARRAY_FIELD_DEFINITION_SERVICE_ID).to(ArrayFieldDefinition).inSingletonScope();
    },

    onStartup({ moduleSystem }) {
        moduleSystem.registerModule({
            onInit: (): void => {
                const colorGroupRegistry = container.get<IconColorGroupsRegistry>(serviceIds.iconColorGroupsRegistry);
                colorGroupRegistry.addToGroup("fieldDefinition", "transformers", "colorCodingPurple1");

                const objectDataRegistry = container.get<DynamicTypeObjectDataRegistry>(
                    serviceIds["DynamicTypes/ObjectDataRegistry"],
                );
                objectDataRegistry.registerDynamicType(container.get(ARRAY_FIELD_SERVICE_ID));

                const fieldDefinitionRegistry = container.get<DynamicTypeFieldDefinitionRegistry>(
                    serviceIds["DynamicTypes/FieldDefinitionRegistry"],
                );
                fieldDefinitionRegistry.registerDynamicType(container.get(ARRAY_FIELD_DEFINITION_SERVICE_ID));

                const arrayFieldGridCell = new ArrayFieldGridCell();
                const gridCellRegistry = container.get<any>(serviceIds["DynamicTypes/GridCellRegistry"]);
                gridCellRegistry.registerDynamicType(arrayFieldGridCell);
            },
        });
    },
};
