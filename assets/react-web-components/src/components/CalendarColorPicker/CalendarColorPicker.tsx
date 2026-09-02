import React, { useCallback, useEffect, useRef, useState } from "react";
import { Check, ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";
import { CALENDAR_COLOR_BLOCKS, isPresetColor, normalizeHex } from "./colors";

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
    <div ref={rootRef} className="inline-flex w-fit flex-col gap-3 align-top">
      {/*
        プリセット色。横=色相 / 縦=明るさ（1列がその色相の濃淡）のグリッド。
        全色相は横幅に収まらないため、色相を上下2グループに分けている
        （各 3行 × 7列）。列数は CALENDAR_COLOR_COLUMNS と grid-cols-7 を
        一致させること。
        グループ間の間隔は行間と同じにして、全体が 6行 × 7列 の一枚の表として
        見えるようにする。

        サイズは rem ではなく px で指定する。CRM 本体が html に font-size:10px を
        設定しているため、rem ベースの指定（h-7 等）は 62.5% に縮んでしまう。
      */}
      <div
        className="flex flex-col gap-[6px]"
        role="listbox"
        aria-label="プリセットカラー"
      >
        {CALENDAR_COLOR_BLOCKS.map((block) => (
          <div key={block[0].hue} className="grid grid-cols-7 gap-[6px]">
            {block.map((swatch) => {
              const selected = normalizeHex(current) === swatch.hex;
              return (
                <button
                  key={swatch.name}
                  type="button"
                  role="option"
                  aria-selected={selected}
                  aria-label={swatch.label}
                  title={swatch.label}
                  onClick={() => commit(swatch.hex)}
                  style={{ backgroundColor: swatch.hex }}
                  className={cn(
                    "flex h-[28px] w-[44px] items-center justify-center rounded-[5px] border border-black/10 transition",
                    "hover:ring-2 hover:ring-gray-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-700 focus-visible:ring-offset-1",
                    selected &&
                      "ring-2 ring-gray-800 ring-offset-1 hover:ring-gray-800",
                  )}
                >
                  {selected && (
                    <Check
                      className="h-[16px] w-[16px]"
                      style={{ color: getReadableTextColor(swatch.hex) }}
                      aria-hidden="true"
                    />
                  )}
                </button>
              );
            })}
          </div>
        ))}
      </div>

      {/* カスタム(RGB)指定 */}
      <div className="flex items-center gap-[8px]">
        <span className="text-base text-gray-700">カスタム</span>

        {/*
          ネイティブのカラーピッカーを開くボタン。input[type=color] 自体は
          見た目を制御できないため透明にして重ね、枠線・ホバー・アイコンで
          「押せる」ことが分かる見た目をラベル側で作る。
          m-0 は CRM 本体の Bootstrap が持つ label{margin-bottom:5px} の打ち消し。
          これが無いと隣の入力欄と縦位置が 2.5px ずれる。
        */}
        <label
          className={cn(
            "relative m-0 flex h-[32px] flex-shrink-0 cursor-pointer items-center gap-[8px] rounded-[6px] border border-input bg-white px-[10px]",
            "shadow-sm transition hover:bg-gray-50 focus-within:ring-2 focus-within:ring-gray-700 focus-within:ring-offset-1",
            usingCustomColor && "ring-2 ring-gray-800 ring-offset-1",
          )}
          title="クリックしてカラーピッカーを開く"
        >
          <span
            className="h-[20px] w-[20px] flex-shrink-0 rounded-[4px] border border-black/10"
            style={{ backgroundColor: colorInputValue }}
            aria-hidden="true"
          />
          <span className="text-base whitespace-nowrap text-gray-700">
            色を選ぶ
          </span>
          <ChevronDown
            className="h-[16px] w-[16px] flex-shrink-0 text-gray-500"
            aria-hidden="true"
          />
          <input
            type="color"
            value={colorInputValue}
            onChange={(e) => commit(e.target.value)}
            className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
          />
        </label>

        {/* 現在の色（HEX）。#込みの7文字が省略されない幅・等幅フォントで表示する */}
        <input
          type="text"
          value={hexDraft}
          onChange={handleHexInputChange}
          onBlur={handleHexInputBlur}
          placeholder="#RRGGBB"
          maxLength={7}
          size={8}
          spellCheck={false}
          autoComplete="off"
          aria-label="カラーコード（16進数）"
          className={cn(
            "m-0 h-[32px] w-[104px] flex-shrink-0 rounded-[6px] border border-input bg-transparent px-[8px] font-mono text-[14px] uppercase outline-none",
            "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[2px]",
          )}
        />
      </div>
    </div>
  );
};

export default CalendarColorPicker;
