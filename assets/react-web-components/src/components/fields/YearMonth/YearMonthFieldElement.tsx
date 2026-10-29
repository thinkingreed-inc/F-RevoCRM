import React, { useState } from "react";
import type { FieldWebComponentRenderProps } from "@/utils/createFieldWebComponent";
import type { YearMonthWebComponentProps } from "@/types/yearMonthField";
import { YearMonthField } from "./YearMonthField";

/**
 * <fr-field-yearmonth> の中身
 *
 * Smarty（Vtiger_YearMonth_UIType::getWebComponentProps）から受け取った props で
 * YearMonthField を出し、値が変わったら hidden 入力（props.inputId）へ書き戻す。
 */
export const YearMonthFieldElement: React.FC<
  FieldWebComponentRenderProps<YearMonthWebComponentProps>
> = ({ props, setValue }) => {
  const [value, setCurrent] = useState(props.value ?? "");
  return (
    <YearMonthField
      mode={props.mode}
      value={value}
      displayFormat={props.displayFormat}
      labels={props.labels}
      fieldLabel={props.fieldLabel}
      mandatory={props.mandatory}
      disabled={props.readonly}
      onChange={(next) => {
        setCurrent(next);
        setValue(next);
      }}
    />
  );
};
