import React, { type ReactElement } from 'react';
import { Input, InputNumber, Button, Space } from 'antd';
import { PlusOutlined, MinusCircleOutlined, UpOutlined, DownOutlined } from '@ant-design/icons';
import { DynamicTypeObjectDataAbstract } from '@pimcore/studio-ui-bundle/modules/element';
import { type ArrayFieldProps } from './array-field-types';

const { TextArea } = Input;

const ArrayFieldComponent = (props: ArrayFieldProps): React.ReactElement => {
  const values = (props.value as any[] | undefined) || [];
  const disabled = props.noteditable === true;

  const handleAddAt = (index: number) => {
    if (props.onChange) {
      const newValues = [...values];
      const defaultValue = props.elementType === 'numeric' ? 0 : '';
      newValues.splice(index, 0, defaultValue);
      props.onChange(newValues);
    }
  };

  const handleRemove = (index: number) => {
    if (props.onChange) {
      const newValues = values.filter((_, i) => i !== index);
      props.onChange(newValues);
    }
  };

  const handleChange = (index: number, value: any) => {
    if (props.onChange) {
      const newValues = [...values];
      newValues[index] = value;
      props.onChange(newValues);
    }
  };

  const renderElementField = (elementType: string, value: any, index: number): React.ReactElement => {
    const commonProps = {
      disabled,
      value,
      placeholder: disabled ? '' : (elementType === 'numeric' ? 'Enter number' : 'Enter value'),
      onChange: (e: any) => {
        const newValue = e?.target?.value !== undefined ? e.target.value : e;
        handleChange(index, newValue);
      }
    };

    switch (elementType) {
      case 'textarea':
        return <TextArea {...commonProps} rows={3} />;
      case 'numeric':
        return <InputNumber {...commonProps} style={{ width: '100%' }} />;
      default:
        return <Input {...commonProps} />;
    }
  };

  return (
    <div style={{ width: '100%' }}>
      {values.length === 0 && !disabled ? (
        <Button type="dashed" onClick={() => handleAddAt(0)} block icon={<PlusOutlined />}>
          Add Item
        </Button>
      ) : (
        values.map((value, index) => (
          <Space key={index} style={{ display: 'flex', marginBottom: 8, width: '100%' }} align="baseline">
            <div style={{ flex: 1 }}>
              {renderElementField(props.elementType, value, index)}
            </div>

            {!disabled && (
              <>
                <Button
                  type="text"
                  icon={<UpOutlined />}
                  onClick={() => handleAddAt(index)}
                  title="Add above"
                />

                <Button
                  type="text"
                  icon={<DownOutlined />}
                  onClick={() => handleAddAt(index + 1)}
                  title="Add below"
                />
              </>
            )}

            <Button
              type="text"
              danger
              icon={<MinusCircleOutlined />}
              onClick={() => handleRemove(index)}
              title="Remove"
              disabled={disabled}
            />
          </Space>
        ))
      )}
    </div>
  );
};

export class DynamicTypeObjectDataArrayField extends DynamicTypeObjectDataAbstract {
  id: string = 'arrayField';
  isCollectionType: boolean = false;
  isAllowedInBatchEdit: boolean = false;

  getObjectDataComponent(props: ArrayFieldProps): ReactElement<ArrayFieldProps> {
    return <ArrayFieldComponent {...props} />;
  }
}
