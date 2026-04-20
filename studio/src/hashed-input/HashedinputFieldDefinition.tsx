import { injectable } from "inversify";
import React from "react";
import { DynamicTypeFieldDefinitionDataAbstract } from "@pimcore/studio-ui-bundle/modules/field-definitions";
import { type ElementIcon } from "@pimcore/studio-ui-bundle/modules/widget-manager";

@injectable()
export class HashedinputFieldDefinition extends DynamicTypeFieldDefinitionDataAbstract {
    id: string = "hashedInput";

    getIcon(): ElementIcon {
        return { type: "name", value: "text-input" };
    }

    getGroup(): string[] {
        return [...super.getGroup(), "text"];
    }

    getSpecificFormFields(): React.JSX.Element {
        return <></>;
    }
}
