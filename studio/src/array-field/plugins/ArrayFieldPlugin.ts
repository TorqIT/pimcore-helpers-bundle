import React, { type ReactElement } from 'react';
import { type IAbstractPlugin, type AbstractModule, container } from "@pimcore/studio-ui-bundle";
import { DynamicTypeObjectDataArrayField } from "../types/dynamic-type-object-data-array-field";
import { DynamicTypeObjectDataRegistry } from "@pimcore/studio-ui-bundle/modules/element";
import { serviceIds } from "@pimcore/studio-ui-bundle/app";
import { type AbstractGridCellDefinition } from "../types/array-field-types";

class DynamicTypeGridCellArrayField {
    readonly id = 'arrayField';

    getGridCellComponent(props: AbstractGridCellDefinition): ReactElement {
        const value = props.getValue();
        const displayValue = Array.isArray(value) && value.length > 0
            ? value.map(v => v === null || v === undefined ? '' : String(v)).join(', ')
            : '';

        return React.createElement('div', {
            className: 'default-cell__content',
            style: {
                overflow: 'hidden',
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap'
            },
            title: displayValue
        }, displayValue);
    }

    getDefaultGridColumnWidth(): number {
        return 300;
    }
}

export const ArrayFieldPlugin: IAbstractPlugin = {
    name: "ArrayFieldPlugin",

    onStartup({ moduleSystem }) {
        moduleSystem.registerModule(ArrayFieldModule);
    },
};

export const ArrayFieldModule: AbstractModule = {
    onInit: (): void => {
        const arrayFieldType = new DynamicTypeObjectDataArrayField();
        const objectDataRegistry = container.get<DynamicTypeObjectDataRegistry>(serviceIds['DynamicTypes/ObjectDataRegistry'])
        objectDataRegistry.registerDynamicType(arrayFieldType);

        const arrayFieldGridCell = new DynamicTypeGridCellArrayField();
        const gridCellRegistry = container.get<any>(serviceIds['DynamicTypes/GridCellRegistry'])
        gridCellRegistry.registerDynamicType(arrayFieldGridCell);
    },
};
