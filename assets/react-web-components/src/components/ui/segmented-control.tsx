import { cn } from "../../lib/utils";

export interface SegmentedOption<T extends string> {
  value: T;
  label: string;
  disabled?: boolean;
}

interface SegmentedControlProps<T extends string> {
  options: readonly SegmentedOption<T>[];
  value: T;
  onChange: (value: T) => void;
  /** ラジオグループの名前（スクリーンリーダー用） */
  ariaLabel: string;
  disabled?: boolean;
  className?: string;
}

/**
 * 択一のセグメントボタン（ラジオグループ）
 *
 * F-RevoCRM 本体の Bootstrap が label 要素に余白・太字を付けるため、label ではなく button で組む。
 * 色は QuickCreate のヘッダー（#596875）に合わせる。
 */
export function SegmentedControl<T extends string>({
  options,
  value,
  onChange,
  ariaLabel,
  disabled = false,
  className,
}: SegmentedControlProps<T>) {
  return (
    <div
      role="radiogroup"
      aria-label={ariaLabel}
      className={cn(
        "inline-grid w-full auto-cols-fr grid-flow-col overflow-hidden rounded border border-gray-300 bg-white sm:w-auto",
        className,
      )}
    >
      {options.map((option) => {
        const checked = option.value === value;
        const isDisabled = disabled || option.disabled === true;
        return (
          <button
            key={option.value}
            type="button"
            role="radio"
            aria-checked={checked}
            disabled={isDisabled}
            onClick={() => {
              if (!checked) onChange(option.value);
            }}
            className={cn(
              "m-0 flex min-h-[32px] items-center justify-center border-0 border-l border-gray-300 px-3 py-1 text-center text-sm leading-tight font-normal whitespace-normal transition-colors first:border-l-0 focus-visible:relative focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[#596875]",
              checked
                ? "bg-[#596875] text-white"
                : "bg-white text-gray-700 hover:bg-gray-100",
              isDisabled && "cursor-not-allowed opacity-50 hover:bg-white",
            )}
          >
            {option.label}
          </button>
        );
      })}
    </div>
  );
}
