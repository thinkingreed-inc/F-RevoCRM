import React, {
  useState,
  useMemo,
  useRef,
  useEffect,
  useCallback,
} from "react";
import { createPortal } from "react-dom";
import { Input } from "./ui/input";
import { X, ChevronDown } from "lucide-react";
import { cn } from "../lib/utils";
import { useOptionalTranslation } from "../hooks/useTranslation";

/**
 * ピックリストオプションの型
 */
interface PicklistOption {
  value: string;
  label: string;
}

/**
 * MultiPicklistFieldのProps
 */
export interface MultiPicklistFieldProps {
  /** フィールド名 */
  name: string;
  /** ラベル */
  label: string;
  /** 現在の値（' |##| ' 区切りの文字列） */
  value?: string;
  /** 値変更時のコールバック */
  onChange: (name: string, value: string) => void;
  /** 選択肢リスト */
  options?: PicklistOption[];
  /** 必須フィールド */
  mandatory?: boolean;
  /** 無効化 */
  disabled?: boolean;
  /** エラーメッセージ */
  error?: string;
  /** カスタムクラス */
  className?: string;
  /** ラベルのカスタムクラス名（縦並び時の左寄せなどに使用） */
  labelClassName?: string;
}

/**
 * MultiPicklistField - 複数選択ピックリストフィールド（UIType 33）用コンポーネント
 * ドロップダウンから選択し、タグ形式で表示
 */
export const MultiPicklistField: React.FC<MultiPicklistFieldProps> = ({
  name,
  label,
  value,
  onChange,
  options = [],
  mandatory = false,
  disabled = false,
  error,
  className,
  labelClassName,
}) => {
  const { t } = useOptionalTranslation();

  // 選択済みの値（配列）
  const selectedValues = useMemo(() => {
    if (!value) return [];
    return value.split(" |##| ").filter(Boolean);
  }, [value]);

  // 検索キーワード
  const [searchTerm, setSearchTerm] = useState<string>("");

  // ドロップダウン表示状態
  const [isOpen, setIsOpen] = useState<boolean>(false);

  // 参照
  const inputRef = useRef<HTMLInputElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const inputContainerRef = useRef<HTMLDivElement>(null);

  // ドロップダウンの位置
  const [dropdownPosition, setDropdownPosition] = useState<{
    top: number;
    left: number;
    width: number;
  } | null>(null);

  // キーボードハイライトindex
  const [highlightedIndex, setHighlightedIndex] = useState<number>(0);

  // タッチスワイプ用のref
  const touchStartYRef = useRef<number | null>(null);

  // Touch event listeners for mobile scrolling (must use passive: false to allow preventDefault)
  useEffect(() => {
    const dropdown = dropdownRef.current;
    if (!dropdown || !isOpen || !dropdownPosition) return;

    const handleTouchStart = (e: TouchEvent) => {
      touchStartYRef.current = e.touches[0].clientY;
    };

    const handleTouchMove = (e: TouchEvent) => {
      if (touchStartYRef.current === null) return;

      const touchCurrentY = e.touches[0].clientY;
      const deltaY = touchStartYRef.current - touchCurrentY;

      dropdown.scrollTop += deltaY;
      touchStartYRef.current = touchCurrentY;

      // Prevent page scroll while scrolling dropdown
      e.preventDefault();
      e.stopPropagation();
    };

    const handleTouchEnd = () => {
      touchStartYRef.current = null;
    };

    dropdown.addEventListener("touchstart", handleTouchStart, {
      passive: true,
    });
    dropdown.addEventListener("touchmove", handleTouchMove, { passive: false });
    dropdown.addEventListener("touchend", handleTouchEnd, { passive: true });

    return () => {
      dropdown.removeEventListener("touchstart", handleTouchStart);
      dropdown.removeEventListener("touchmove", handleTouchMove);
      dropdown.removeEventListener("touchend", handleTouchEnd);
    };
  }, [isOpen, dropdownPosition]);

  /**
   * 検索でフィルタリングされたオプション（選択済みを除外）
   */
  const filteredOptions = useMemo(() => {
    // 選択済みの項目を除外
    let filtered = options.filter((opt) => !selectedValues.includes(opt.value));

    // 検索キーワードでさらにフィルタリング
    if (searchTerm) {
      const lowerSearch = searchTerm.toLowerCase();
      filtered = filtered.filter(
        (opt) =>
          opt.label.toLowerCase().includes(lowerSearch) ||
          opt.value.toLowerCase().includes(lowerSearch),
      );
    }
    return filtered;
  }, [options, searchTerm, selectedValues]);

  // 検索語変更時はハイライトをリセット
  useEffect(() => {
    setHighlightedIndex(0);
  }, [searchTerm]);

  // 候補数変動時のクランプ（連続選択UX: 選択した位置に次候補がスライドインするためindex維持＋範囲外クランプ）
  useEffect(() => {
    setHighlightedIndex((prev) => {
      if (filteredOptions.length === 0) return 0;
      return Math.min(prev, filteredOptions.length - 1);
    });
  }, [filteredOptions.length]);

  /**
   * 選択済みの値からラベルを取得
   */
  const getLabel = (val: string): string => {
    const option = options.find((opt) => opt.value === val);
    return option ? option.label : val;
  };

  /**
   * 検索入力変更
   */
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
    setIsOpen(true);
  };

  /**
   * オプション選択（ドロップダウンからは追加のみ、削除はタグの×ボタンで行う）
   */
  const handleSelectOption = (optionValue: string) => {
    // 選択追加
    const newValues = [...selectedValues, optionValue];
    onChange(name, newValues.join(" |##| "));
  };

  /**
   * タグ削除
   */
  const handleRemoveTag = (optionValue: string) => {
    const newValues = selectedValues.filter((v) => v !== optionValue);
    onChange(name, newValues.join(" |##| "));
  };

  /**
   * キーボード操作（↑↓循環・Enter選択・Esc閉）
   */
  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (!isOpen || filteredOptions.length === 0) return;

      switch (e.key) {
        case "ArrowDown":
          e.preventDefault();
          setHighlightedIndex((prev) =>
            prev < filteredOptions.length - 1 ? prev + 1 : 0,
          );
          break;
        case "ArrowUp":
          e.preventDefault();
          setHighlightedIndex((prev) =>
            prev > 0 ? prev - 1 : filteredOptions.length - 1,
          );
          break;
        case "Enter":
          // IME(日本語入力)確定のEnterはオプション選択しない
          if (e.nativeEvent.isComposing) break;
          e.preventDefault();
          if (
            highlightedIndex >= 0 &&
            highlightedIndex < filteredOptions.length
          ) {
            handleSelectOption(filteredOptions[highlightedIndex].value);
          }
          break;
        case "Escape":
          // ドロップダウンを閉じるのみ。Dialog自体への伝播は QuickCreate の
          // onEscapeKeyDown が data-rwc-dropdown 要素の存在で判定して抑止する
          setIsOpen(false);
          setHighlightedIndex(0);
          break;
      }
    },
    [isOpen, filteredOptions, highlightedIndex],
  );

  /**
   * ドロップダウンの位置を計算
   */
  const updateDropdownPosition = useCallback(() => {
    if (inputContainerRef.current) {
      const rect = inputContainerRef.current.getBoundingClientRect();
      setDropdownPosition({
        top: rect.bottom + window.scrollY,
        left: rect.left + window.scrollX,
        width: rect.width,
      });
    }
  }, []);

  /**
   * フォーカス時にドロップダウンを開く
   */
  const handleFocus = () => {
    updateDropdownPosition();
    setIsOpen(true);
  };

  /**
   * クリックアウトサイドでドロップダウンを閉じる
   */
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Node;
      const isInsideDropdown = dropdownRef.current?.contains(target);
      const isInsideInput = inputContainerRef.current?.contains(target);

      if (!isInsideDropdown && !isInsideInput) {
        setIsOpen(false);
        setSearchTerm("");
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  /**
   * スクロール・リサイズ時にドロップダウンの位置を更新
   */
  useEffect(() => {
    if (!isOpen) return;

    const handleScrollOrResize = () => {
      updateDropdownPosition();
    };

    window.addEventListener("scroll", handleScrollOrResize, true);
    window.addEventListener("resize", handleScrollOrResize);

    return () => {
      window.removeEventListener("scroll", handleScrollOrResize, true);
      window.removeEventListener("resize", handleScrollOrResize);
    };
  }, [isOpen, updateDropdownPosition]);

  // ドロップダウンのレンダリング（Portal使用）
  const renderDropdown = () => {
    if (!isOpen || disabled || !dropdownPosition) return null;

    const dropdown = (
      <div
        ref={dropdownRef}
        data-rwc-dropdown="multipicklist"
        className="fixed z-[100003] bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto pointer-events-auto"
        style={{
          top: dropdownPosition.top,
          left: dropdownPosition.left,
          width: dropdownPosition.width,
        }}
        onWheel={(e) => {
          e.stopPropagation();
          const target = e.currentTarget;
          target.scrollTop += e.deltaY * 0.3;
        }}
      >
        {filteredOptions.length > 0 ? (
          <div className="py-1">
            {filteredOptions.map((option, index) => (
              <div
                key={option.value}
                onClick={() => handleSelectOption(option.value)}
                className={cn(
                  "px-3 py-1.5 text-md cursor-pointer",
                  index === highlightedIndex
                    ? "bg-blue-100"
                    : "hover:bg-blue-50",
                )}
              >
                {option.label}
              </div>
            ))}
          </div>
        ) : (
          <div className="px-3 py-1.5 text-md text-gray-500 text-center">
            {selectedValues.length > 0
              ? t("LBL_ALL_OPTIONS_SELECTED")
              : t("LBL_NO_OPTIONS_AVAILABLE")}
          </div>
        )}
      </div>
    );

    return createPortal(dropdown, document.body);
  };

  return (
    <div className={cn("flex items-start gap-2", className)}>
      {labelClassName ? (
        <div className="flex items-baseline md:contents">
          <span
            className={cn(
              "text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]",
              disabled && "text-gray-400",
              labelClassName,
            )}
          >
            {label}
            {mandatory && (
              <span className="text-red-500" aria-hidden="true">
                *
              </span>
            )}
            {mandatory && <span className="sr-only"> (必須)</span>}
          </span>
          <span
            className="w-3 flex-shrink-0 hidden md:block"
            aria-hidden="true"
          />
        </div>
      ) : (
        <>
          <span
            className={cn(
              "text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]",
              disabled && "text-gray-400",
            )}
          >
            {label}
            {mandatory && <span className="sr-only"> (必須)</span>}
          </span>
          <span
            className="w-3 leading-[30px] text-red-500 text-center flex-shrink-0"
            aria-hidden="true"
          >
            {mandatory ? "*" : ""}
          </span>
        </>
      )}

      {/* 入力エリア */}
      <div className="flex-1 min-w-0">
        {/* 検索入力 */}
        <div className="relative" ref={inputContainerRef}>
          <div className="relative flex items-center">
            <Input
              ref={inputRef}
              id={`field_${name}`}
              type="text"
              value={searchTerm}
              onChange={handleSearchChange}
              onFocus={handleFocus}
              onKeyDown={handleKeyDown}
              onBlur={() => {
                // Tab離脱等の blur でドロップダウンを閉じる
                // 候補クリック時の選択処理を妨げないよう150ms遅延
                setTimeout(() => setIsOpen(false), 150);
              }}
              disabled={disabled}
              placeholder={t("LBL_PLACEHOLDER_SELECT", label)}
              autoComplete="off"
              className={cn("pr-10", error && "border-red-500")}
            />

            {/* ドロップダウンアイコン */}
            <div className="absolute right-3 flex items-center pointer-events-none">
              <ChevronDown className="w-4 h-4 text-gray-400" />
            </div>
          </div>

          {/* ドロップダウン */}
          {renderDropdown()}
        </div>

        {/* 選択済みタグ表示 */}
        {selectedValues.length > 0 && (
          <div className="mt-2 flex flex-wrap gap-2">
            {selectedValues.map((val) => (
              <div
                key={val}
                className="relative inline-flex items-center bg-blue-100 text-blue-800 rounded-md text-md"
              >
                <span className="pl-3 pr-6 py-1.5">{getLabel(val)}</span>
                {!disabled && (
                  <button
                    type="button"
                    onClick={() => handleRemoveTag(val)}
                    className="absolute right-0 top-0 bottom-0 w-6 flex items-center justify-center hover:bg-blue-200 rounded-r-md transition-colors cursor-pointer"
                    aria-label={`${getLabel(val)}を削除`}
                  >
                    <X className="w-4 h-4" />
                  </button>
                )}
              </div>
            ))}
          </div>
        )}

        {/* 隠しフィールド */}
        <input type="hidden" name={name} value={value || ""} />

        {/* エラーメッセージ */}
        {error && (
          <div className="mt-1 text-sm text-red-600 flex items-center">
            <svg
              className="w-4 h-4 mr-1"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fillRule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
            {error}
          </div>
        )}
      </div>
    </div>
  );
};

export default MultiPicklistField;
