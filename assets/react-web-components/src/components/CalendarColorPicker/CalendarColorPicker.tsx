import React, { useCallback, useEffect, useRef, useState } from "react";
import { Check } from "lucide-react";
import { cn } from "@/lib/utils";
import { CALENDAR_COLOR_PALETTE, isPresetColor, normalizeHex } from "./colors";

/** カスタム(RGB)入力の初期表示に使うフォールバック色 */
const FALLBACK_COLOR = "#3b82f6";

export interface CalendarColorPickerProps {
  /** 現在の色（#rrggbb）。外部から属性で渡され、フィールド/ユーザー変更時の自動設定にも使われる */
  value?: string;
  /** 色確定時に発火するコールバック（Web Component では "change" イベントとして発火） */
  onChange?: (hex: string) => void;
}

/**
 * 選択中の色に対して視認できる文字色（白 or 黒）を返す。
 * チェックマークの色決定に使用する。
 */
function getReadableTextColor(hex: string): string {
  const normalized = normalizeHex(hex);
  if (!normalized) {
    return "#000000";
  }
  const r = parseInt(normalized.slice(1, 3), 16);
  const g = parseInt(normalized.slice(3, 5), 16);
  const b = parseInt(normalized.slice(5, 7), 16);
  // 知覚輝度（YIQ 近似）で明暗を判定
  const luminance = 0.299 * r + 0.587 * g + 0.114 * b;
  return luminance > 150 ? "#000000" : "#ffffff";
}

/**
 * CalendarColorPicker
 *
 * カレンダーの色選択UI。Tailwind のプリセット色から選ぶか、
 * カスタム(RGB)で自由に指定できる。
 *
 * このコンポーネントは UI のみを担い、選択結果は同一 <form> 内の
 * hidden input（input.selectedColor）へ #rrggbb 形式で書き込む。
 * 保存・送信などの周辺処理は既存の実装をそのまま利用する。
 */
export const CalendarColorPicker: React.FC<CalendarColorPickerProps> = ({
  value,
  onChange,
}) => {
  const rootRef = useRef<HTMLDivElement>(null);
  const [current, setCurrent] = useState<string>(
    () => normalizeHex(value) ?? "",
  );
  // カスタムHEXテキスト入力の下書き（確定前の自由入力を保持）
  const [hexDraft, setHexDraft] = useState<string>(
    () => normalizeHex(value) ?? "",
  );

  /**
   * 同一フォーム内の hidden input（.selectedColor）へ色を書き込み、
   * 既存の保存処理が読み取れるようにする。
   */
  const writeToSelectedColorInput = useCallback((hex: string) => {
    const input = rootRef.current
      ?.closest("form")
      ?.querySelector<HTMLInputElement>("input.selectedColor");
    if (input) {
      input.value = hex;
      input.dispatchEvent(new Event("input", { bubbles: true }));
      input.dispatchEvent(new Event("change", { bubbles: true }));
    }
  }, []);

  /**
   * 外部（value 属性）からの色指定に追従する。
   * 例: 対象フィールド／ユーザーを切り替えたときの色の自動設定。
   */
  useEffect(() => {
    const normalized = normalizeHex(value);
    if (normalized) {
      setCurrent(normalized);
      setHexDraft(normalized);
      writeToSelectedColorInput(normalized);
    }
  }, [value, writeToSelectedColorInput]);

  /** 色を確定し、状態・hidden input・コールバックへ反映する */
  const commit = useCallback(
    (hex: string) => {
      const normalized = normalizeHex(hex);
      if (!normalized) {
        return;
      }
      setCurrent(normalized);
      setHexDraft(normalized);
      writeToSelectedColorInput(normalized);
      onChange?.(normalized);
    },
    [onChange, writeToSelectedColorInput],
  );

  /** HEXテキスト入力の変更（有効な値なら即時確定） */
  const handleHexInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const raw = e.target.value;
    setHexDraft(raw);
    if (normalizeHex(raw)) {
      commit(raw);
    }
  };

  /** HEXテキストからフォーカスが外れたとき、不正な下書きは現在値に戻す */
  const handleHexInputBlur = () => {
    if (!normalizeHex(hexDraft)) {
      setHexDraft(current);
    }
  };

  const hasSelection = current !== "";
  const usingCustomColor = hasSelection && !isPresetColor(current);
  const colorInputValue = normalizeHex(current) ?? FALLBACK_COLOR;

  return (
    <div ref={rootRef} className="inline-flex flex-col gap-3 align-top">
      {/* プリセット色（Tailwind パレット） */}
      <div
        className="flex max-w-[320px] flex-wrap gap-1.5"
        role="listbox"
        aria-label="プリセットカラー"
      >
        {CALENDAR_COLOR_PALETTE.map((swatch) => {
          const selected = normalizeHex(current) === swatch.hex;
          return (
            <button
              key={swatch.name}
              type="button"
              role="option"
              aria-selected={selected}
              aria-label={swatch.name}
              title={swatch.name}
              onClick={() => commit(swatch.hex)}
              style={{ backgroundColor: swatch.hex }}
              className={cn(
                "flex h-6 w-6 items-center justify-center rounded-md border border-black/10 transition-transform",
                "hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-700 focus-visible:ring-offset-1",
                selected && "ring-2 ring-gray-800 ring-offset-1",
              )}
            >
              {selected && (
                <Check
                  className="h-4 w-4"
                  style={{ color: getReadableTextColor(swatch.hex) }}
                  aria-hidden="true"
                />
              )}
            </button>
          );
        })}
      </div>

      {/* カスタム(RGB)指定 */}
      <div className="flex items-center gap-2">
        <span className="text-md text-gray-700">カスタム</span>
        <label
          className={cn(
            "relative flex h-6 w-6 cursor-pointer items-center justify-center rounded-md border border-black/10",
            usingCustomColor && "ring-2 ring-gray-800 ring-offset-1",
          )}
          style={{ backgroundColor: colorInputValue }}
          title="カスタムカラーを選択"
        >
          {/* ネイティブのカラーピッカー（RGB指定）。見た目はラベルの背景色で表現 */}
          <input
            type="color"
            value={colorInputValue}
            onChange={(e) => commit(e.target.value)}
            className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
            aria-label="カスタムカラー（RGB）"
          />
          {usingCustomColor && (
            <Check
              className="pointer-events-none h-4 w-4"
              style={{ color: getReadableTextColor(colorInputValue) }}
              aria-hidden="true"
            />
          )}
        </label>
        <input
          type="text"
          value={hexDraft}
          onChange={handleHexInputChange}
          onBlur={handleHexInputBlur}
          placeholder="#RRGGBB"
          maxLength={7}
          spellCheck={false}
          autoComplete="off"
          aria-label="カラーコード（16進数）"
          className={cn(
            "h-6 w-24 rounded-sm border border-input bg-transparent px-2 text-md uppercase outline-none",
            "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[2px]",
          )}
        />
      </div>

      {/* 現在の選択プレビュー */}
      <div className="flex items-center gap-2">
        <span
          className="h-4 w-4 flex-shrink-0 rounded-sm border border-black/10"
          style={{
            backgroundColor: hasSelection ? colorInputValue : "transparent",
          }}
          aria-hidden="true"
        />
        <span className="text-md text-gray-600">
          {hasSelection ? current.toUpperCase() : "未選択"}
        </span>
      </div>
    </div>
  );
};

export default CalendarColorPicker;
