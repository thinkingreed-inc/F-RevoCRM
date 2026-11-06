/**
 * リード昇格・リードマッピング設定の API 呼び出し
 */
import type { FieldInfo } from "../../types/field";
import type {
  QuickCreateFieldData,
  QuickCreateFieldsResponse,
} from "../../types/quickcreate";
import type { ApiErrorResponse } from "../../types/leadConvert";

function getCsrfToken(): { name: string; value: string } | null {
  const csrfName = (window as { csrfMagicName?: string }).csrfMagicName;
  const csrfToken = (window as { csrfMagicToken?: string }).csrfMagicToken;
  return csrfName && csrfToken ? { name: csrfName, value: csrfToken } : null;
}

async function parse<T>(response: Response): Promise<T> {
  const text = await response.text();
  let data: unknown;
  try {
    data = JSON.parse(text);
  } catch {
    throw new Error(`Invalid response (HTTP ${response.status})`);
  }
  const error = data as ApiErrorResponse;
  if (error && (error.success === false || error.error)) {
    throw new Error(
      error.error?.message || error.error?.code || "Request failed",
    );
  }
  const wrapped = data as { result?: T };
  return (wrapped.result ?? data) as T;
}

/** GET index.php?…（JSON） */
export async function getJson<T>(params: Record<string, string>): Promise<T> {
  const response = await fetch(`index.php?${new URLSearchParams(params)}`, {
    method: "GET",
    credentials: "same-origin",
    headers: { Accept: "application/json" },
  });
  return parse<T>(response);
}

/** POST index.php（フォーム形式、CSRF トークン付き） */
export async function postJson<T>(params: Record<string, string>): Promise<T> {
  const body = new URLSearchParams();
  const csrf = getCsrfToken();
  if (csrf) {
    body.append(csrf.name, csrf.value);
  }
  for (const [key, value] of Object.entries(params)) {
    body.append(key, value);
  }
  const response = await fetch("index.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body: body.toString(),
  });
  return parse<T>(response);
}

/** GetFields の項目を FieldRenderer 用の FieldInfo にする */
export function toFieldInfo(apiField: QuickCreateFieldData): FieldInfo {
  return {
    name: apiField.name,
    label: apiField.label,
    uitype: apiField.uitype,
    mandatory: apiField.mandatory || false,
    readonly: apiField.readonly || false,
    maxlength: apiField.maxlength,
    block: apiField.block,
    fieldinfo: {
      ...(apiField.fieldinfo ?? {}),
      type: apiField.type || "string",
      defaultvalue: apiField.defaultValue,
      editable: apiField.editable,
      displaytype: apiField.displaytype,
      block: apiField.block,
      referenceModules: apiField.referenceModules,
    },
    picklistValues: apiField.picklistValues,
    referenceModules: apiField.referenceModules,
    referenceModuleLabels: apiField.referenceModuleLabels,
    customValidations: apiField.customValidations,
    isJoditEditor: apiField.isJoditEditor,
    datatype: apiField.datatype,
    isMultiple: apiField.isMultiple,
    taxClassDetails: apiField.taxClassDetails,
  };
}

/** 編集画面と同じ項目（GetFields view=edit）を取る */
export async function fetchEditFields(module: string): Promise<FieldInfo[]> {
  const data = await getJson<QuickCreateFieldsResponse>({
    module,
    api: "GetFields",
    view: "edit",
  });
  return (data.fields ?? [])
    .filter(
      (field) => field.editable && field.displaytype !== "2" && !field.readonly,
    )
    .map(toFieldInfo);
}
