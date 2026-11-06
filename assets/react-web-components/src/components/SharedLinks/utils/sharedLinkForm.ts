import type {
  SharedLink,
  SharedLinkInput,
  SharedLinkLimits,
  SharedLinkTarget,
  SharedLinkTargetType,
} from "@/types/sharedLinks";

/**
 * 共有リンクの編集フォームの補助（画面に依存しない処理）
 *
 * 入力の検証はサーバー側（Settings_SharedLinks_Rules_Model::validate）と同じ規則で、
 * エラーのキー（項目名）と言語ファイルのキーもそろえている。
 */

/** 画面で選ぶ対象の種類（役割の「下位を含む」はチェックボックスで分ける） */
export type TargetKind = "Users" | "Groups" | "Roles";

/** 新規追加のときの初期値 */
export function createEmptyLink(): SharedLinkInput {
  return {
    title: "",
    url: "",
    description: "",
    method: "GET",
    open_mode: "tab",
    window_width: null,
    window_height: null,
    noreferrer: false,
    active: true,
    visibility: "all",
    params: [],
    targets: [],
  };
}

/** 一覧のリンクを編集フォームの値にする（表示用の情報は落とす） */
export function toInput(link: SharedLink): SharedLinkInput {
  return {
    title: link.title,
    url: link.url,
    description: link.description,
    method: link.method,
    open_mode: link.open_mode,
    window_width: link.window_width,
    window_height: link.window_height,
    noreferrer: link.noreferrer,
    active: link.active,
    visibility: link.visibility,
    params: link.params.map(({ name, value_type, value }) => ({
      name,
      value_type,
      value,
    })),
    targets: link.targets.map(({ type, id }) => ({ type, id })),
  };
}

/** 保存する直前の形にそろえる（前後の空白・使わない値を落とす） */
export function toPayload(input: SharedLinkInput): SharedLinkInput {
  const isWindow = input.open_mode === "window";
  return {
    ...input,
    title: input.title.trim(),
    url: input.url.trim(),
    description: input.description.trim(),
    window_width: isWindow ? input.window_width : null,
    window_height: isWindow ? input.window_height : null,
    params: input.params.map((param) => ({
      ...param,
      name: param.name.trim(),
    })),
    targets: input.visibility === "targets" ? input.targets : [],
  };
}

/** 画面の種類と「下位の役割も含む」から、保存する種類を決める */
export function toTargetType(
  kind: TargetKind,
  includeSubordinates: boolean,
): SharedLinkTargetType {
  if (kind === "Roles" && includeSubordinates) {
    return "RoleAndSubordinates";
  }
  return kind;
}

/** 対象の重複判定に使うキー */
export function targetKey(target: SharedLinkTarget): string {
  return `${target.type}:${target.id}`;
}

/** 同じ対象（種類と ID が同じもの）が既にあるか */
export function hasTarget(
  targets: SharedLinkTarget[],
  target: SharedLinkTarget,
): boolean {
  const key = targetKey(target);
  return targets.some((t) => targetKey(t) === key);
}

/** ウィジェットの見出しに使えない文字（サーバーの WIDGET_TITLE_FORBIDDEN と同じ） */
export const WIDGET_TITLE_FORBIDDEN = ["<", ">", "&", '"', "'"];

/**
 * ウィジェットの見出しを検証する（必須・上限・使えない文字）
 *
 * ほかのウィジェットと名前が重ならないかはサーバーで確かめる。
 *
 * @returns エラー（言語ファイルのキー）。妥当なら null
 */
export function validateWidgetTitle(
  title: string,
  limits: SharedLinkLimits,
): string | null {
  const value = title.trim();
  if (value === "") {
    return "LBL_ERROR_WIDGET_TITLE_REQUIRED";
  }
  if ([...value].length > limits.widget_title_max) {
    return "LBL_ERROR_WIDGET_TITLE_TOO_LONG";
  }
  if (WIDGET_TITLE_FORBIDDEN.some((char) => value.includes(char))) {
    return "LBL_ERROR_WIDGET_TITLE_CHARS";
  }
  return null;
}

/** http / https の URL か */
export function isValidHttpUrl(value: string): boolean {
  let parsed: URL;
  try {
    parsed = new URL(value);
  } catch {
    return false;
  }
  return (
    (parsed.protocol === "http:" || parsed.protocol === "https:") &&
    parsed.hostname !== ""
  );
}

/**
 * 入力を検証する
 *
 * @param allowedUserFields 差し込める項目名
 * @returns 項目ごとのエラー（値は言語ファイルのキー）。空なら妥当
 */
export function validateSharedLink(
  input: SharedLinkInput,
  limits: SharedLinkLimits,
  allowedUserFields: string[],
): Record<string, string> {
  const link = toPayload(input);
  const errors: Record<string, string> = {};

  if (link.title === "") {
    errors.title = "LBL_ERROR_TITLE_REQUIRED";
  } else if ([...link.title].length > limits.title_max) {
    errors.title = "LBL_ERROR_TITLE_TOO_LONG";
  }

  if (link.url === "") {
    errors.url = "LBL_ERROR_URL_REQUIRED";
  } else if ([...link.url].length > limits.url_max) {
    errors.url = "LBL_ERROR_URL_TOO_LONG";
  } else if (!isValidHttpUrl(link.url)) {
    errors.url = "LBL_ERROR_URL_INVALID";
  }

  if ([...link.description].length > limits.description_max) {
    errors.description = "LBL_ERROR_DESCRIPTION_TOO_LONG";
  }

  if (link.open_mode === "window") {
    for (const key of ["window_width", "window_height"] as const) {
      const size = link[key];
      if (
        size === null ||
        !Number.isInteger(size) ||
        size < limits.window_min ||
        size > limits.window_max
      ) {
        errors[key] = "LBL_ERROR_WINDOW_SIZE";
      }
    }
  }

  if (link.params.length > limits.params_max) {
    errors.params = "LBL_ERROR_TOO_MANY_PARAMS";
  }
  link.params.forEach((param, index) => {
    if (param.name === "") {
      errors[`params.${index}.name`] = "LBL_ERROR_PARAM_NAME_REQUIRED";
    } else if ([...param.name].length > limits.param_name_max) {
      errors[`params.${index}.name`] = "LBL_ERROR_PARAM_NAME_TOO_LONG";
    }
    if (param.value_type === "fixed") {
      if ([...param.value].length > limits.param_value_max) {
        errors[`params.${index}.value`] = "LBL_ERROR_PARAM_VALUE_TOO_LONG";
      }
    } else if (param.value === "") {
      errors[`params.${index}.value`] = "LBL_ERROR_USER_FIELD_REQUIRED";
    } else if (!allowedUserFields.includes(param.value)) {
      errors[`params.${index}.value`] = "LBL_ERROR_USER_FIELD_INVALID";
    }
  });

  if (link.visibility === "targets" && link.targets.length === 0) {
    errors.targets = "LBL_ERROR_TARGETS_REQUIRED";
  }

  return errors;
}
