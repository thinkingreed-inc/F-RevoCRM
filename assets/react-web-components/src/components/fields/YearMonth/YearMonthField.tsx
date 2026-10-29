import React, { useState } from "react";
import { CalendarDays, ChevronLeft, ChevronRight, X } from "lucide-react";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import { useOptionalTranslation } from "@/hooks/useTranslation";
import type { WebComponentFieldMode } from "@/types/webComponentField";
import type { YearMonthLabels } from "@/types/yearMonthField";
import {
  currentYearMonth,
  formatYearMonth,
  parseYearMonth,
  toDbValue,
} from "./yearMonth";

/** 言語ファイルから渡されないときの表記 */
const FALLBACK_LABELS: Record<string, string> = {
  LBL_YEARMONTH_PLACEHOLDER: "年月を選択",
  LBL_YEARMONTH_THIS_MONTH: "今月",
  LBL_YEARMONTH_CLEAR: "クリア",
  LBL_YEARMONTH_PREV_YEAR: "前の年",
  LBL_YEARMONTH_NEXT_YEAR: "次の年",
  LBL_YEARMONTH_YEAR_FORMAT: "{Y}年",
};

export interface YearMonthFieldProps {
  mode: WebComponentFieldMode;
  /** 値（月初日 YYYY-MM-01。未入力は空文字） */
  value: string;
  /** 表示の書式（{Y} 年、{M} 月、{MM} 月（2桁）） */
  displayFormat?: string;
  labels?: YearMonthLabels;
  fieldLabel?: string;
  mandatory?: boolean;
  disabled?: boolean;
  /** 値（月初日。クリアしたら空文字）が変わったとき */
  onChange?: (value: string) => void;
  className?: string;
}

/**
 * 年月の入力欄
 *
 * 編集: ボタンを押すと年と月を選ぶ一覧が開く。選んだ値は月初日（YYYY-MM-01）で onChange へ渡す。
 * 詳細: 値を文字で出すだけ。
 */
export const YearMonthField: React.FC<YearMonthFieldProps> = ({
  mode,
  value,
  displayFormat = "{Y}年{M}月",
  labels,
  fieldLabel,
  mandatory = false,
  disabled = false,
  onChange,
  className,
}) => {
  const label = (key: string) => labels?.[key] || FALLBACK_LABELS[key] || key;
  const monthLabel = (month: number) =>
    labels?.[`LBL_YEARMONTH_MONTH_${month}`] || `${month}月`;
  const selected = parseYearMonth(value);
  const [open, setOpen] = useState(false);
  const [viewYear, setViewYear] = useState(
    () => (selected ?? currentYearMonth()).year,
  );
  const text = formatYearMonth(selected, displayFormat);

  if (mode === "detail") {
    return <span className={className}>{text}</span>;
  }

  const select = (year: number, month: number) => {
    onChange?.(toDbValue({ year, month }));
    setOpen(false);
  };
  const thisMonth = currentYearMonth();

  return (
    <div className={cn("flex w-full items-center gap-1", className)}>
      <Popover
        open={open}
        onOpenChange={(next) => {
          if (next) setViewYear((selected ?? currentYearMonth()).year);
          setOpen(next);
        }}
      >
        <PopoverTrigger asChild>
          <button
            type="button"
            data-testid="yearmonth-trigger"
            disabled={disabled}
            aria-label={fieldLabel}
            aria-required={mandatory || undefined}
            className={cn(
              // 他の入力欄（.inputElement）と同じ高さ・枠・文字色にそろえる
              "flex h-[30px] w-full items-center justify-between gap-2 rounded-[1px] border border-solid border-[#ccc] bg-white px-2 py-[3px] text-left text-[14px] shadow-none",
              "outline-none focus-visible:border-[#66afe9] disabled:cursor-not-allowed disabled:bg-[#eee]",
              text ? "text-[#555]" : "text-[#999]",
            )}
          >
            <span>{text || label("LBL_YEARMONTH_PLACEHOLDER")}</span>
            <CalendarDays className="size-4 shrink-0" aria-hidden />
          </button>
        </PopoverTrigger>
        <PopoverContent
          align="start"
          className="z-[100005] w-64 rounded-[2px] border border-solid border-[#ccc] bg-white p-3 shadow-[0_2px_8px_rgba(0,0,0,0.15)]"
          onOpenAutoFocus={(event) => event.preventDefault()}
        >
          <div className="mb-2 flex items-center justify-between">
            <button
              type="button"
              aria-label={label("LBL_YEARMONTH_PREV_YEAR")}
              className="inline-flex size-7 items-center justify-center rounded-[1px] border-0 bg-transparent text-[#555] hover:bg-[#eef1f4]"
              onClick={() => setViewYear((year) => year - 1)}
            >
              <ChevronLeft className="size-4" aria-hidden />
            </button>
            <span
              className="text-[14px] font-medium text-[#333]"
              data-testid="yearmonth-year"
            >
              {formatYearMonth(
                { year: viewYear, month: 1 },
                label("LBL_YEARMONTH_YEAR_FORMAT"),
              )}
            </span>
            <button
              type="button"
              aria-label={label("LBL_YEARMONTH_NEXT_YEAR")}
              className="inline-flex size-7 items-center justify-center rounded-[1px] border-0 bg-transparent text-[#555] hover:bg-[#eef1f4]"
              onClick={() => setViewYear((year) => year + 1)}
            >
              <ChevronRight className="size-4" aria-hidden />
            </button>
          </div>
          <div className="grid grid-cols-3 gap-1">
            {Array.from({ length: 12 }, (_, index) => index + 1).map(
              (month) => {
                const isSelected =
                  selected?.year === viewYear && selected.month === month;
                const isThisMonth =
                  thisMonth.year === viewYear && thisMonth.month === month;
                return (
                  <button
                    key={month}
                    type="button"
                    aria-pressed={isSelected}
                    data-month={month}
                    className={cn(
                      "h-[30px] rounded-[1px] border border-solid text-[14px]",
                      isSelected
                        ? "border-[#3f5166] bg-[#3f5166] text-white"
                        : "border-[#ddd] bg-white text-[#555] hover:bg-[#eef1f4]",
                      !isSelected && isThisMonth && "border-[#3f5166]",
                    )}
                    onClick={() => select(viewYear, month)}
                  >
                    {monthLabel(month)}
                  </button>
                );
              },
            )}
          </div>
          <div className="mt-2 flex justify-between border-0 border-t border-solid border-[#e5e5e5] pt-2 text-[14px]">
            <button
              type="button"
              className="border-0 bg-transparent p-0 text-[#3f5166] hover:underline"
              onClick={() => select(thisMonth.year, thisMonth.month)}
            >
              {label("LBL_YEARMONTH_THIS_MONTH")}
            </button>
            {!mandatory && (
              <button
                type="button"
                className="border-0 bg-transparent p-0 text-[#999] hover:underline"
                onClick={() => {
                  onChange?.("");
                  setOpen(false);
                }}
              >
                {label("LBL_YEARMONTH_CLEAR")}
              </button>
            )}
          </div>
        </PopoverContent>
      </Popover>
      {selected && !mandatory && !disabled && (
        <button
          type="button"
          data-testid="yearmonth-clear"
          aria-label={label("LBL_YEARMONTH_CLEAR")}
          className="inline-flex size-7 shrink-0 items-center justify-center rounded-[1px] border-0 bg-transparent text-[#999] hover:bg-[#eef1f4]"
          onClick={() => onChange?.("")}
        >
          <X className="size-4" aria-hidden />
        </button>
      )}
    </div>
  );
};

const TRANSLATED_KEYS = [
  ...Object.keys(FALLBACK_LABELS),
  ...Array.from(
    { length: 12 },
    (_, index) => `LBL_YEARMONTH_MONTH_${index + 1}`,
  ),
];

/**
 * 翻訳の仕組み（TranslationContext）から文言を取る年月の入力欄
 *
 * React 版のクイック作成など、Smarty から文言を渡されない画面で使う。
 * 翻訳が無い文言は YearMonthField の既定の表記になる。
 */
export const YearMonthFieldWithTranslation: React.FC<
  Omit<YearMonthFieldProps, "labels" | "displayFormat">
> = (props) => {
  const { t } = useOptionalTranslation();
  const labels: YearMonthLabels = {};
  TRANSLATED_KEYS.forEach((key) => {
    const translated = t(key);
    if (translated !== key) labels[key] = translated;
  });
  const format = t("LBL_YEARMONTH_FORMAT");
  return (
    <YearMonthField
      {...props}
      labels={labels}
      displayFormat={format !== "LBL_YEARMONTH_FORMAT" ? format : undefined}
    />
  );
};
