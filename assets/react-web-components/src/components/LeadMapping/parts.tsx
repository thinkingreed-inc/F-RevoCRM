import { CheckCircle2, Loader2, XCircle } from "lucide-react";
import { Button } from "../ui/button";
import { SegmentedControl } from "../ui/segmented-control";

export type Translate = (key: string, ...args: (string | number)[]) => string;

export const selectClassName =
  "h-[32px] w-full min-w-0 rounded border border-gray-300 bg-white px-2 text-sm text-gray-800 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500";

/** 昇格先モジュールの切り替え（セグメント） */
export function ModuleSwitcher<T extends string>({
  modules,
  labels,
  value,
  onChange,
  name,
}: {
  modules: readonly T[];
  labels: Record<T, string>;
  value: T;
  onChange: (module: T) => void;
  name: string;
}) {
  return (
    <SegmentedControl
      ariaLabel={name}
      value={value}
      onChange={onChange}
      options={modules.map((module) => ({
        value: module,
        label: labels[module],
      }))}
    />
  );
}

/** 保存ボタンと結果の表示 */
export function SaveBar({
  t,
  saving,
  dirty,
  message,
  errors,
  onSave,
  testId,
}: {
  t: Translate;
  saving: boolean;
  dirty: boolean;
  message: string | null;
  errors: string[];
  onSave: () => void;
  testId: string;
}) {
  return (
    <div className="sticky bottom-0 z-10 -mx-4 mt-4 flex flex-col gap-2 border-t border-gray-200 bg-white/95 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <div className="text-sm" aria-live="polite">
        {errors.length > 0 && (
          <ul className="m-0 list-none space-y-1 p-0 text-red-700">
            {errors.map((error) => (
              <li key={error} className="flex items-start gap-1">
                <XCircle
                  className="mt-0.5 h-4 w-4 shrink-0"
                  aria-hidden="true"
                />
                {error}
              </li>
            ))}
          </ul>
        )}
        {errors.length === 0 && message && (
          <span className="flex items-center gap-1 text-green-700">
            <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
            {message}
          </span>
        )}
        {errors.length === 0 && !message && dirty && (
          <span className="text-amber-700">
            {t("LBL_CONVERT_SETTINGS_UNSAVED")}
          </span>
        )}
      </div>
      <Button
        type="button"
        onClick={onSave}
        disabled={saving || !dirty}
        className="h-auto px-6 py-1.5 text-md font-bold !bg-green-600 !text-white hover:!bg-green-700"
        data-testid={testId}
      >
        {saving && (
          <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
        )}
        {t("LBL_CONVERT_SETTINGS_SAVE")}
      </Button>
    </div>
  );
}
