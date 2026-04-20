import React, { type ReactElement } from 'react';

export interface AbstractGridCellDefinition {
  getValue: () => any;
  [key: string]: any;
}

export abstract class DynamicTypeGridCellAbstract {
  abstract readonly id: string;
  abstract getGridCellComponent(props: AbstractGridCellDefinition): ReactElement;
  getDefaultGridColumnWidth?(): number | undefined;
}

/**
 * Grid Cell for Array Field - displays all values comma-delimited
 */
export class DynamicTypeGridCellArrayField extends DynamicTypeGridCellAbstract {
  readonly id = 'arrayField';

  getGridCellComponent(props: AbstractGridCellDefinition): ReactElement {
    const value = props.getValue();

    const displayValue = Array.isArray(value) && value.length > 0
      ? value.filter(v => v !== null && v !== undefined && v !== '').join(', ')
      : '';

    return (
      <div
        className="default-cell__content"
        style={{
          overflow: 'hidden',
          textOverflow: 'ellipsis',
          whiteSpace: 'nowrap'
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
