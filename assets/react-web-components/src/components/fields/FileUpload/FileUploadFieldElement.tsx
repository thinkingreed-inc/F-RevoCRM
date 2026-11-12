import React from "react";
import { TranslationProvider } from "@/contexts/TranslationContext";
import type { FieldWebComponentRenderProps } from "@/utils/createFieldWebComponent";
import type { FileUploadWebComponentProps } from "@/types/fileUploadField";
import { FileUploadField } from "./FileUploadField";

/**
 * <fr-field-fileupload> の中身
 *
 * Smarty（Vtiger_Fileupload_UIType::getWebComponentProps）から受け取った props で
 * FileUploadField を出し、値が変わったら hidden 入力（props.inputId）へ書き戻す。
 * 文言は Documents の言語ファイルから取る。
 */
export const FileUploadFieldElement: React.FC<
  FieldWebComponentRenderProps<FileUploadWebComponentProps>
> = ({ props, setValue }) => (
  <TranslationProvider module="Documents">
    <FileUploadField
      module={props.module}
      fieldName={props.fieldName}
      fieldLabel={props.fieldLabel}
      mode={props.mode}
      recordId={props.recordId}
      file={props.file}
      value={props.value}
      handwriting={props.handwriting}
      maxUploadSize={props.maxUploadSize}
      disabled={props.readonly}
      onChange={(documentId) => setValue(documentId)}
    />
  </TranslationProvider>
);
