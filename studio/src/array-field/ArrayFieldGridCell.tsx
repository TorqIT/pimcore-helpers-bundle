import { AbstractGridCellDefinition, DynamicTypeGridCellAbstract } from "@pimcore/studio-ui-bundle/modules/element";
import React, { type ReactElement } from "react";

/**
 * Grid Cell for Array Field - displays all values comma-delimited
 */
export class ArrayFieldGridCell extends DynamicTypeGridCellAbstract {
    readonly id = "arrayField";

    getGridCellComponent(props: AbstractGridCellDefinition): ReactElement {
        const value = props.getValue();

        const displayValue =
            Array.isArray(value) && value.length > 0
                ? value.filter((v) => v !== null && v !== undefined && v !== "").join(", ")
                : "";

        return (
            <div
                className="default-cell__content"
                style={{
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                    whiteSpace: "nowrap",
                }}
                title={displayValue}
            >
                {displayValue}
            </div>
        );
    }

    getDefaultGridColumnWidth(): number | undefined {
        return 300;
    }
}
