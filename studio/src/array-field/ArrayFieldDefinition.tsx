import { injectable } from "inversify";
import React from "react";
import { DynamicTypeFieldDefinitionDataAbstract } from "@pimcore/studio-ui-bundle/modules/field-definitions";
import { type ElementIcon } from "@pimcore/studio-ui-bundle/modules/widget-manager";

@injectable()
export class ArrayFieldDefinition extends DynamicTypeFieldDefinitionDataAbstract {
    id: string = "arrayField";

    getIcon(): ElementIcon {
        return { type: "name", value: "transformers" };
    }

    getGroup(): string[] {
        return [...super.getGroup(), "structured"];
    }

    getSpecificFormFields(): React.JSX.Element {
        return <></>;
    }
}
