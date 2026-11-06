import React, { useState } from "react";
import type { FieldWebComponentRenderProps } from "@/utils/createFieldWebComponent";
import type { PicklistWebComponentProps } from "@/types/picklistField";
import { PicklistFieldView } from "./PicklistFieldView";
import { serializeSelected } from "./picklistValue";

/**
 * <fr-field-picklist> / <fr-field-multipicklist> の中身（#1677）
 *
 * Smarty（PicklistDisplayConfig::buildProps）から受け取った props で表示し、
 * 選択が変わったら hidden 入力（props.inputId）へ ' |##| ' 連結（複数）または
 * 単一値で書き戻す（送信・必須検証は hidden 側が担う）。
 * 編集時のみ値を書き戻す（detail では表示のみ）。
 */
export const PicklistFieldElement: React.FC<
  FieldWebComponentRenderProps<PicklistWebComponentProps>
> = ({ props, setValue }) => {
  const multiple = Boolean(props.multiple);
  const isEdit = props.mode === "edit";
  const [selected, setSelected] = useState<string[]>(
    Array.isArray(props.value) ? props.value : [],
  );

  const handleChange = (next: string[]) => {
    setSelected(next);
    if (isEdit) {
      setValue(serializeSelected(next, multiple));
    }
  };

  return (
    <PicklistFieldView
      fieldName={props.fieldName}
      options={Array.isArray(props.options) ? props.options : []}
      selected={selected}
      multiple={multiple}
      effectiveStyle={props.effectiveStyle}
      columns={Number(props.columns) === 2 ? 2 : 1}
      mandatory={Boolean(props.mandatory)}
      disabled={!isEdit || Boolean(props.readonly)}
      onChange={handleChange}
    />
  );
};

export default PicklistFieldElement;
