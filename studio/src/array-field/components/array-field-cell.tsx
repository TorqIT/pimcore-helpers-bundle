import React from 'react';

export interface ArrayFieldCellProps {
  getValue: () => any;
  [key: string]: any;
}

/**
 * Grid Cell Component for Array Field
 * Studio UI receives raw array data, display all values comma-delimited
 */
export const ArrayFieldCell = (props: ArrayFieldCellProps): React.JSX.Element => {
  const { getValue } = props;
  const value = getValue();

  let displayValue = '';

  if (Array.isArray(value) && value.length > 0) {
    // Join all values with comma and space
    displayValue = value.filter(v => v !== null && v !== undefined && v !== '').join(', ');
  }

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
};
