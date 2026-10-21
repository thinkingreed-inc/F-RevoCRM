/**
 * リード昇格画面の状態に関するルール（React に依存しない純粋な関数）
 *
 * - 企業か担当者のどちらかは必須
 * - 必須の参照項目（担当者→企業、案件→企業、案件→担当者）の参照先は強制的に作成または紐づけ
 * - 判定に当たったレコードは既存として最初からセットする
 */
import type { FieldInfo, FieldValue } from "../../../types/field";
import {
  CONVERT_MODULES,
  type AttachableModule,
  type ConvertConstraints,
  type ConvertError,
  type ConvertLeadInfo,
  type ConvertMode,
  type ConvertModule,
  type ConvertRequestPayload,
  type ConvertSectionState,
  type ConvertSections,
} from "../../../types/leadConvert";
import { validateFieldByUIType } from "../../../utils/validation";
import { runCustomValidations } from "../../../utils/customValidation";

type Translate = (key: string, ...args: (string | number)[]) => string;

export type FieldsByModule = Record<ConvertModule, FieldInfo[]>;

export type FieldErrors = Record<ConvertModule, Record<string, string>>;

/** 既存レコード未選択のエラーを入れるキー */
export const EXISTING_ERROR_KEY = "__existing";

export function emptyFieldErrors(): FieldErrors {
  return { Accounts: {}, Contacts: {}, Potentials: {} };
}

function isOn(section: ConvertSectionState): boolean {
  return section.mode !== "none";
}

/** 表示中の項目（設定の項目 + エラーで追加した項目） */
export function visibleFields(
  info: ConvertLeadInfo,
  sections: ConvertSections,
  module: ConvertModule,
): string[] {
  const fields = [...info.modules[module].fields];
  for (const name of sections[module].extraFields) {
    if (!fields.includes(name)) {
      fields.push(name);
    }
  }
  return fields;
}

function existingFromMatch(
  info: ConvertLeadInfo,
  module: ConvertModule,
): Pick<ConvertSectionState, "existingId" | "existingLabel"> | null {
  const record = info.modules[module].match?.records[0];
  return record
    ? { existingId: String(record.id), existingLabel: record.label }
    : null;
}

/**
 * どのモジュールがどのモジュールの必須項目のために強制されているか
 *
 * @returns 強制されるモジュール => 強制している側のモジュール
 */
export function forcedBy(
  sections: ConvertSections,
  constraints: ConvertConstraints,
): Partial<Record<ConvertModule, ConvertModule>> {
  const forced: Partial<Record<ConvertModule, ConvertModule>> = {};
  if (isOn(sections.Potentials) && constraints.potentialAccountMandatory) {
    forced.Accounts = "Potentials";
  }
  if (isOn(sections.Potentials) && constraints.potentialContactMandatory) {
    forced.Contacts = "Potentials";
  }
  if (isOn(sections.Contacts) && constraints.contactAccountMandatory) {
    forced.Accounts = "Contacts";
  }
  return forced;
}

/** 強制されているのに作らないことになっているモジュールを ON にする */
function applyForced(
  sections: ConvertSections,
  info: ConvertLeadInfo,
): ConvertSections {
  let next = sections;
  // 担当者を ON にすると企業も強制されることがあるため、変化が無くなるまで繰り返す
  for (let i = 0; i < CONVERT_MODULES.length; i++) {
    const forced = forcedBy(next, info.constraints);
    let changed = false;
    for (const module of CONVERT_MODULES) {
      if (forced[module] && next[module].mode === "none") {
        const existing = existingFromMatch(info, module);
        next = {
          ...next,
          [module]: existing
            ? { ...next[module], mode: "existing", ...existing }
            : { ...next[module], mode: "create" },
        };
        changed = true;
      }
    }
    if (!changed) {
      break;
    }
  }
  return next;
}

export function initialSections(info: ConvertLeadInfo): ConvertSections {
  const build = (module: ConvertModule): ConvertSectionState => {
    const moduleInfo = info.modules[module];
    const values: Record<string, FieldValue> = { ...moduleInfo.values };
    for (const [name, label] of Object.entries(moduleInfo.referenceLabels)) {
      values[`${name}_display`] = label;
    }
    const state: ConvertSectionState = {
      mode: "none",
      existingId: "",
      existingLabel: "",
      values,
      extraFields: [],
    };
    if (!moduleInfo.available) {
      return state;
    }
    const existing = existingFromMatch(info, module);
    if (existing && module !== "Potentials") {
      return { ...state, mode: "existing", ...existing };
    }
    if (module === "Contacts") {
      return { ...state, mode: "create" };
    }
    if (module === "Accounts") {
      const name = String(moduleInfo.values.accountname ?? "").trim();
      return { ...state, mode: name !== "" ? "create" : "none" };
    }
    return state;
  };

  return applyForced(
    {
      Accounts: build("Accounts"),
      Contacts: build("Contacts"),
      Potentials: build("Potentials"),
    },
    info,
  );
}

/**
 * セクションの扱いを切り替える。強制されているモジュールは「作成しない」にできない
 */
export function changeMode(
  sections: ConvertSections,
  module: ConvertModule,
  mode: ConvertMode,
  info: ConvertLeadInfo,
): ConvertSections {
  if (mode === "none" && forcedBy(sections, info.constraints)[module]) {
    return sections;
  }
  return applyForced(
    { ...sections, [module]: { ...sections[module], mode } },
    info,
  );
}

function isEmpty(value: FieldValue): boolean {
  return (
    value === undefined ||
    value === null ||
    value === false ||
    (typeof value === "string" && value.trim() === "") ||
    (Array.isArray(value) && value.length === 0)
  );
}

/** 画面側の検証（QuickCreate と同じ: 必須・最大長・uitype・カスタムバリデーション） */
export function validateSections(
  sections: ConvertSections,
  fieldsByModule: FieldsByModule,
  info: ConvertLeadInfo,
  t: Translate,
): { fieldErrors: FieldErrors; generalErrors: string[] } {
  const fieldErrors = emptyFieldErrors();
  const generalErrors: string[] = [];
  if (!isOn(sections.Accounts) && !isOn(sections.Contacts)) {
    generalErrors.push(t("LBL_CONVERT_REQUIRE_ONE"));
  }
  for (const module of CONVERT_MODULES) {
    const section = sections[module];
    if (section.mode === "existing") {
      if (section.existingId === "") {
        fieldErrors[module][EXISTING_ERROR_KEY] = t(
          "LBL_CONVERT_EXISTING_REQUIRED",
          info.modules[module].label,
        );
      }
      continue;
    }
    if (section.mode !== "create") {
      continue;
    }
    const names = visibleFields(info, sections, module);
    for (const field of fieldsByModule[module]) {
      if (!names.includes(field.name)) {
        continue;
      }
      const value = section.values[field.name];
      let error: string | null = null;
      if (field.mandatory && isEmpty(value)) {
        error = t("LBL_FIELD_REQUIRED", field.label);
      } else if (
        field.maxlength &&
        typeof value === "string" &&
        value.length > field.maxlength
      ) {
        error = t("LBL_FIELD_MAX_LENGTH", field.label, field.maxlength);
      } else if (!isEmpty(value)) {
        error =
          validateFieldByUIType(field, value, t) ??
          runCustomValidations(field, value, section.values);
      }
      if (error) {
        fieldErrors[module][field.name] = error;
      }
    }
  }
  return { fieldErrors, generalErrors };
}

export function hasErrors(
  fieldErrors: FieldErrors,
  generalErrors: string[],
): boolean {
  return (
    generalErrors.length > 0 ||
    CONVERT_MODULES.some(
      (module) => Object.keys(fieldErrors[module]).length > 0,
    )
  );
}

export function defaultTransfer(sections: ConvertSections): AttachableModule {
  return isOn(sections.Contacts) ? "Contacts" : "Accounts";
}

/** ConvertLeadSave に送る data を作る（表示中の項目だけを送る） */
export function buildPayload(
  sections: ConvertSections,
  info: ConvertLeadInfo,
  assignedUserId: string,
  transferTo: AttachableModule,
): ConvertRequestPayload {
  const modules = {} as ConvertRequestPayload["modules"];
  for (const module of CONVERT_MODULES) {
    const section = sections[module];
    if (section.mode === "existing") {
      modules[module] = {
        mode: "existing",
        existingId: Number(section.existingId),
        values: {},
      };
    } else if (section.mode === "create") {
      const values: Record<string, FieldValue> = {};
      for (const name of visibleFields(info, sections, module)) {
        values[name] = section.values[name] ?? "";
      }
      modules[module] = { mode: "create", values };
    } else {
      modules[module] = { mode: "none", values: {} };
    }
  }
  const transfer =
    sections[transferTo].mode !== "none"
      ? transferTo
      : defaultTransfer(sections);
  return { modules, assignedUserId, transferTo: transfer };
}

/**
 * サーバー側の検証エラーを画面に反映する
 *
 * 画面に出していない項目のエラーは、その項目を追加表示して直せるようにする。
 * 編集できない項目や項目に紐づかないエラーは全体のエラーとして出す。
 */
export function applyServerErrors(
  sections: ConvertSections,
  errors: ConvertError[],
  fieldsByModule: FieldsByModule,
): {
  sections: ConvertSections;
  fieldErrors: FieldErrors;
  generalErrors: string[];
} {
  const fieldErrors = emptyFieldErrors();
  const generalErrors: string[] = [];
  let next = sections;
  for (const error of errors) {
    const module = error.module;
    const known =
      module !== "" &&
      error.field !== "" &&
      fieldsByModule[module].some((field) => field.name === error.field);
    if (module === "" || error.field === "" || !error.editable || !known) {
      generalErrors.push(error.message);
      continue;
    }
    fieldErrors[module][error.field] = error.message;
    if (error.hidden && !next[module].extraFields.includes(error.field)) {
      const current = next[module].values[error.field];
      next = {
        ...next,
        [module]: {
          ...next[module],
          extraFields: [...next[module].extraFields, error.field],
          values:
            current === undefined || current === ""
              ? { ...next[module].values, [error.field]: error.value ?? "" }
              : next[module].values,
        },
      };
    }
  }
  return { sections: next, fieldErrors, generalErrors };
}
