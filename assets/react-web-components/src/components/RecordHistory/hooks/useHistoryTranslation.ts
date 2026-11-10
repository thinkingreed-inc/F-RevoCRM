import { useCallback } from "react";
import { useOptionalTranslationContext } from "@/contexts/TranslationContext";
import { RECORD_HISTORY_DEFAULTS } from "../translations";

export type HistoryTranslate = (
  key: string,
  ...args: (string | number)[]
) => string;

function format(template: string, args: (string | number)[]): string {
  let index = 0;
  return template.replace(/%s/g, () =>
    index < args.length ? String(args[index++]) : "%s",
  );
}

/**
 * サーバー翻訳 → record-history の既定文言 → キー の順で解決する翻訳関数。
 */
export function useHistoryTranslation(): HistoryTranslate {
  const { translations } = useOptionalTranslationContext();
  return useCallback(
    (key: string, ...args: (string | number)[]) => {
      const template = translations[key] ?? RECORD_HISTORY_DEFAULTS[key] ?? key;
      return args.length ? format(template, args) : template;
    },
    [translations],
  );
}
