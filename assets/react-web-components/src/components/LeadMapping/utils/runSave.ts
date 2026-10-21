import type { Translate } from "../parts";

/** 保存系 API を呼んで結果をまとめる */
export async function runSave(
  action: () => Promise<{
    saved: boolean;
    errors: Array<{ field: string; message: string }>;
  }>,
  t: Translate,
): Promise<{
  saved: boolean;
  errors: string[];
  fieldErrors: Record<string, string>;
}> {
  try {
    const result = await action();
    const fieldErrors: Record<string, string> = {};
    for (const error of result.errors) {
      if (error.field) {
        fieldErrors[error.field] = error.message;
      }
    }
    return {
      saved: result.saved,
      errors: result.saved ? [] : result.errors.map((error) => error.message),
      fieldErrors,
    };
  } catch (err) {
    return {
      saved: false,
      errors: [
        err instanceof Error
          ? err.message
          : t("LBL_CONVERT_SETTINGS_LOAD_FAILED"),
      ],
      fieldErrors: {},
    };
  }
}
