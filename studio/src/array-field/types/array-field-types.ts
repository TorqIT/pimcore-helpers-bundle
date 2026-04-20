import { type AbstractObjectDataDefinition } from '@pimcore/studio-ui-bundle/modules/element';

export interface ArrayFieldProps extends AbstractObjectDataDefinition {
    elementType: string;
    removeDuplicates: boolean;
}

export interface AbstractGridCellDefinition {
    getValue: () => any;
    [key: string]: any;
}
