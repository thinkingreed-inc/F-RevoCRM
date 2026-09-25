import React, { useState, useEffect, useRef, useCallback } from "react";
import { createPortal } from "react-dom";
import { Search, X, ChevronDown, Loader2 } from "lucide-react";
import { Input } from "./ui/input";
import { Button } from "./ui/button";
import { RecordSearchDrawer } from "./RecordSearchDrawer";
import { cn } from "../lib/utils";
import { useOptionalTranslation } from "../hooks/useTranslation";

/**
 * 参照レコードの型
 */
interface ReferenceRecord {
  id: string;
  label: string;
  module: string;
}

/**
 * ReferenceFieldのProps
 */
export interface ReferenceFieldProps {
  /** フィールド名 */
  name: string;
  /** ラベル */
  label: string;
  /** 参照先モジュール名の配列 */
  referenceModules: string[];
  /** 参照先モジュールの翻訳ラベル（モジュール名 -> 翻訳ラベルのマップ） */
  referenceModuleLabels?: Record<string, string>;
  /** 現在の値（レコードID） */
  value?: string;
  /** 現在のラベル（表示用） */
  displayValue?: string;
  /** 値変更時のコールバック */
  onChange: (name: string, value: string, record?: ReferenceRecord) => void;
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
 * ReferenceField - 参照フィールド（UIType 10）用コンポーネント
 * 検索可能なセレクトボックスとして実装
 */
export const ReferenceField: React.FC<ReferenceFieldProps> = ({
  name,
  label,
  referenceModules,
  referenceModuleLabels = {},
  value,
  displayValue,
  onChange,
  mandatory = false,
  disabled = false,
  error,
  className,
  labelClassName,
}) => {
  // 翻訳フック（TranslationProvider外でも安全に使用可能）
  const { t } = useOptionalTranslation();

  /**
   * モジュール名の表示ラベルを取得（翻訳があれば翻訳、なければモジュール名そのまま）
   */
  const getModuleLabel = (moduleName: string): string => {
    return referenceModuleLabels[moduleName] || moduleName;
  };
  // 現在選択中のモジュール（複数参照の場合）
  const [selectedModule, setSelectedModule] = useState<string>(
    referenceModules.length > 0 ? referenceModules[0] : "",
  );

  // 検索キーワード
  const [searchTerm, setSearchTerm] = useState<string>("");

  // 表示用のラベル
  const [displayLabel, setDisplayLabel] = useState<string>(displayValue || "");

  // 検索結果
  const [searchResults, setSearchResults] = useState<ReferenceRecord[]>([]);

  // ドロップダウン表示状態
  const [isOpen, setIsOpen] = useState<boolean>(false);

  // ローディング状態
  const [isLoading, setIsLoading] = useState<boolean>(false);

  // Drawer表示状態
  const [isDrawerOpen, setIsDrawerOpen] = useState<boolean>(false);

  // モジュール選択ドロップダウン表示状態
  const [isModuleDropdownOpen, setIsModuleDropdownOpen] =
    useState<boolean>(false);

  // 検索入力へのref
  const inputRef = useRef<HTMLInputElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const moduleDropdownRef = useRef<HTMLDivElement>(null);
  const inputContainerRef = useRef<HTMLDivElement>(null);
  const moduleInputContainerRef = useRef<HTMLDivElement>(null);

  // ドロップダウンの位置
  const [dropdownPosition, setDropdownPosition] = useState<{
    top: number;
    left: number;
    width: number;
  } | null>(null);
  const [moduleDropdownPosition, setModuleDropdownPosition] = useState<{
    top: number;
    left: number;
    width: number;
  } | null>(null);

  // キーボード操作用ハイライトindex (検索結果ドロップダウン)
  const [highlightedIndex, setHighlightedIndex] = useState<number>(0);

  // キーボード操作用ハイライトindex (モジュール選択ドロップダウン)
  const [moduleHighlightedIndex, setModuleHighlightedIndex] =
    useState<number>(0);

  // デバウンス用のタイマー
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // タッチスワイプ用のref
  const touchStartYRef = useRef<number | null>(null);
  const moduleTouchStartYRef = useRef<number | null>(null);

  // Touch event listeners for search dropdown (must use passive: false to allow preventDefault)
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

  // Touch event listeners for module dropdown (must use passive: false to allow preventDefault)
  useEffect(() => {
    const dropdown = moduleDropdownRef.current;
    if (!dropdown || !isModuleDropdownOpen || !moduleDropdownPosition) return;

    const handleTouchStart = (e: TouchEvent) => {
      moduleTouchStartYRef.current = e.touches[0].clientY;
    };

    const handleTouchMove = (e: TouchEvent) => {
      if (moduleTouchStartYRef.current === null) return;

      const touchCurrentY = e.touches[0].clientY;
      const deltaY = moduleTouchStartYRef.current - touchCurrentY;

      dropdown.scrollTop += deltaY;
      moduleTouchStartYRef.current = touchCurrentY;

      // Prevent page scroll while scrolling dropdown
      e.preventDefault();
      e.stopPropagation();
    };

    const handleTouchEnd = () => {
      moduleTouchStartYRef.current = null;
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
  }, [isModuleDropdownOpen, moduleDropdownPosition]);

  /**
   * レコード検索
   */
  const searchRecords = useCallback(
    async (searchValue: string) => {
      if (!selectedModule) return;

      setIsLoading(true);
      try {
        const params = new URLSearchParams({
          module: selectedModule,
          api: "SearchRecords",
          search: searchValue,
          limit: "20",
        });

        const response = await fetch(`?${params.toString()}`, {
          method: "GET",
          credentials: "same-origin",
          headers: {
            Accept: "application/json",
          },
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        // APIレスポンス構造: { success: true, result: { records: [...] } } または直接 { records: [...] }
        const records = data.result?.records || data.records || [];
        setSearchResults(records);
      } catch (err) {
        console.error("Reference search error:", err);
        setSearchResults([]);
      } finally {
        setIsLoading(false);
      }
    },
    [selectedModule],
  );

  /**
   * 検索キーワード変更時（デバウンス付き）
   */
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setSearchTerm(newValue);
    setDisplayLabel(newValue);

    // 既存の選択をクリア
    if (value) {
      onChange(name, "");
    }

    // デバウンス
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }

    debounceRef.current = setTimeout(() => {
      searchRecords(newValue);
    }, 300);
  };

  /**
   * コンポーネントアンマウント時にデバウンスタイマーをクリーンアップ
   */
  useEffect(() => {
    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, []);

  // 検索語変更時 + searchResults更新時にハイライトを先頭にリセット
  useEffect(() => {
    setHighlightedIndex(0);
  }, [searchTerm, searchResults]);

  // モジュール選択ドロップダウンopen時に現在の選択モジュールにハイライト
  useEffect(() => {
    if (isModuleDropdownOpen) {
      const currentIndex = referenceModules.findIndex(
        (m) => m === selectedModule,
      );
      setModuleHighlightedIndex(currentIndex >= 0 ? currentIndex : 0);
    }
  }, [isModuleDropdownOpen, referenceModules, selectedModule]);

  /**
   * レコード選択
   */
  const handleSelectRecord = (record: ReferenceRecord) => {
    setDisplayLabel(record.label);
    setSearchTerm("");
    setIsOpen(false);
    onChange(name, record.id, record);
  };

  /**
   * 選択をクリア
   */
  const handleClear = () => {
    setDisplayLabel("");
    setSearchTerm("");
    onChange(name, "");
    setSearchResults([]);
    inputRef.current?.focus();
  };

  /**
   * キーボード操作 (検索結果ドロップダウン用 ↑↓ Enter Esc)
   */
  const handleSearchKeyDown = useCallback(
    (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (!isOpen || isLoading || searchResults.length === 0) return;

      switch (e.key) {
        case "ArrowDown":
          e.preventDefault();
          setHighlightedIndex((prev) =>
            prev < searchResults.length - 1 ? prev + 1 : 0,
          );
          break;
        case "ArrowUp":
          e.preventDefault();
          setHighlightedIndex((prev) =>
            prev > 0 ? prev - 1 : searchResults.length - 1,
          );
          break;
        case "Enter":
          // IME(日本語入力)確定のEnterはレコード選択しない
          if (e.nativeEvent.isComposing) break;
          e.preventDefault();
          if (
            highlightedIndex >= 0 &&
            highlightedIndex < searchResults.length
          ) {
            handleSelectRecord(searchResults[highlightedIndex]);
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
    [isOpen, isLoading, searchResults, highlightedIndex],
  );

  /**
   * 参照モジュール選択
   */
  const handleModuleSelect = (moduleName: string) => {
    setSelectedModule(moduleName);
    setIsModuleDropdownOpen(false);
    handleClear();
  };

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
   * モジュールドロップダウンの位置を計算
   */
  const updateModuleDropdownPosition = useCallback(() => {
    if (moduleInputContainerRef.current) {
      const rect = moduleInputContainerRef.current.getBoundingClientRect();
      setModuleDropdownPosition({
        top: rect.bottom + window.scrollY,
        left: rect.left + window.scrollX,
        width: rect.width,
      });
    }
  }, []);

  /**
   * フォーカス時に候補を表示
   */
  const handleFocus = () => {
    updateDropdownPosition();
    setIsOpen(true);
    if (searchResults.length === 0 && !searchTerm) {
      searchRecords("");
    }
  };

  /**
   * Drawerでレコード選択
   */
  const handleDrawerSelect = (record: ReferenceRecord) => {
    setDisplayLabel(record.label);
    setSearchTerm("");
    onChange(name, record.id, record);
  };

  /**
   * クリックアウトサイドでドロップダウンを閉じる
   */
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Node;

      // 検索結果ドロップダウン
      const isInsideSearchDropdown = dropdownRef.current?.contains(target);
      const isInsideSearchInput = inputContainerRef.current?.contains(target);
      if (!isInsideSearchDropdown && !isInsideSearchInput) {
        setIsOpen(false);
      }

      // モジュール選択ドロップダウン
      const isInsideModuleDropdown =
        moduleDropdownRef.current?.contains(target);
      const isInsideModuleInput =
        moduleInputContainerRef.current?.contains(target);
      if (!isInsideModuleDropdown && !isInsideModuleInput) {
        setIsModuleDropdownOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  /**
   * スクロール・リサイズ時にドロップダウンの位置を更新
   */
  useEffect(() => {
    if (!isOpen && !isModuleDropdownOpen) return;

    const handleScrollOrResize = () => {
      if (isOpen) updateDropdownPosition();
      if (isModuleDropdownOpen) updateModuleDropdownPosition();
    };

    window.addEventListener("scroll", handleScrollOrResize, true);
    window.addEventListener("resize", handleScrollOrResize);

    return () => {
      window.removeEventListener("scroll", handleScrollOrResize, true);
      window.removeEventListener("resize", handleScrollOrResize);
    };
  }, [
    isOpen,
    isModuleDropdownOpen,
    updateDropdownPosition,
    updateModuleDropdownPosition,
  ]);

  /**
   * 初期表示ラベルの設定
   */
  useEffect(() => {
    if (displayValue) {
      setDisplayLabel(displayValue);
    }
  }, [displayValue]);

  // モジュール選択ドロップダウンのレンダリング（Portal使用）
  const renderModuleDropdown = () => {
    if (!isModuleDropdownOpen || disabled || !moduleDropdownPosition)
      return null;

    const dropdown = (
      <div
        ref={moduleDropdownRef}
        data-rwc-dropdown="reference-module"
        className="fixed z-[100003] bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto pointer-events-auto"
        style={{
          top: moduleDropdownPosition.top,
          left: moduleDropdownPosition.left,
          width: moduleDropdownPosition.width,
        }}
        onWheel={(e) => {
          e.stopPropagation();
          // Radix UIのDialogがbodyのスクロールをブロックするため、手動でスクロール
          const target = e.currentTarget;
          target.scrollTop += e.deltaY * 0.3;
        }}
      >
        <div className="py-1">
          {referenceModules.map((mod, index) => (
            <div
              key={mod}
              onClick={() => handleModuleSelect(mod)}
              className={cn(
                "px-3 py-1.5 text-md cursor-pointer",
                index === moduleHighlightedIndex
                  ? "bg-blue-100"
                  : selectedModule === mod
                    ? "bg-blue-100"
                    : "hover:bg-blue-50",
              )}
            >
              {getModuleLabel(mod)}
            </div>
          ))}
        </div>
      </div>
    );

    return createPortal(dropdown, document.body);
  };

  // 検索結果ドロップダウンのレンダリング（Portal使用）
  const renderSearchDropdown = () => {
    if (!isOpen || disabled || !dropdownPosition) return null;

    const dropdown = (
      <div
        ref={dropdownRef}
        data-rwc-dropdown="reference-search"
        className="fixed z-[100003] bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto pointer-events-auto"
        style={{
          top: dropdownPosition.top,
          left: dropdownPosition.left,
          width: dropdownPosition.width,
        }}
        onWheel={(e) => {
          e.stopPropagation();
          // Radix UIのDialogがbodyのスクロールをブロックするため、手動でスクロール
          const target = e.currentTarget;
          target.scrollTop += e.deltaY * 0.3;
        }}
      >
        {isLoading ? (
          <div className="px-3 py-1.5 text-md text-gray-500 text-center">
            検索中...
          </div>
        ) : searchResults.length > 0 ? (
          <div className="py-1">
            {searchResults.map((record, index) => (
              <div
                key={record.id}
                onClick={() => handleSelectRecord(record)}
                className={cn(
                  "px-3 py-1.5 text-md cursor-pointer",
                  index === highlightedIndex
                    ? "bg-blue-100"
                    : value === record.id
                      ? "bg-blue-100"
                      : "hover:bg-blue-50",
                )}
              >
                {record.label}
              </div>
            ))}
          </div>
        ) : (
          <div className="px-3 py-1.5 text-md text-gray-500 text-center">
            {searchTerm
              ? "該当するレコードがありません"
              : "レコードがありません"}
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
        {/* 検索入力 + モジュール選択 */}
        <div className="relative">
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            {/* 複数参照モジュールの場合のモジュール選択（モバイル縦並び、PC横並び） */}
            {referenceModules.length > 1 && (
              <div
                className="relative flex-shrink-0 w-full sm:w-auto"
                ref={moduleInputContainerRef}
              >
                <div className="relative flex items-center">
                  <Input
                    type="text"
                    value={getModuleLabel(selectedModule)}
                    disabled={disabled}
                    onClick={() => {
                      if (!disabled) {
                        updateModuleDropdownPosition();
                        setIsModuleDropdownOpen(!isModuleDropdownOpen);
                      }
                    }}
                    onKeyDown={(e) => {
                      if (disabled) return;
                      // ドロップダウン閉時: Enter/Space で開く
                      if (!isModuleDropdownOpen) {
                        if (e.key === "Enter" || e.key === " ") {
                          e.preventDefault();
                          updateModuleDropdownPosition();
                          setIsModuleDropdownOpen(true);
                        }
                        return;
                      }
                      // ドロップダウン開時: ↑↓Enter Esc
                      switch (e.key) {
                        case "ArrowDown":
                          e.preventDefault();
                          setModuleHighlightedIndex((prev) =>
                            prev < referenceModules.length - 1 ? prev + 1 : 0,
                          );
                          break;
                        case "ArrowUp":
                          e.preventDefault();
                          setModuleHighlightedIndex((prev) =>
                            prev > 0 ? prev - 1 : referenceModules.length - 1,
                          );
                          break;
                        case "Enter":
                        case " ":
                          // IME(日本語入力)確定のEnterはモジュール選択しない
                          if (e.nativeEvent.isComposing) break;
                          e.preventDefault();
                          if (
                            moduleHighlightedIndex >= 0 &&
                            moduleHighlightedIndex < referenceModules.length
                          ) {
                            handleModuleSelect(
                              referenceModules[moduleHighlightedIndex],
                            );
                          }
                          break;
                        case "Escape":
                          // ドロップダウンを閉じるのみ。Dialog自体への伝播は QuickCreate の
                          // onEscapeKeyDown が data-rwc-dropdown 要素の存在で判定して抑止する
                          setIsModuleDropdownOpen(false);
                          break;
                      }
                    }}
                    onBlur={() => {
                      // Tab離脱等の blur でモジュール選択ドロップダウンを閉じる
                      // 候補クリック時の選択処理を妨げないよう150ms遅延
                      setTimeout(() => setIsModuleDropdownOpen(false), 150);
                    }}
                    onChange={() => {}} // 値は選択肢からのみ変更可能
                    autoComplete="off"
                    className={cn(
                      "w-full sm:w-[90px] sm:max-w-[90px] pr-8 cursor-pointer caret-transparent",
                      disabled && "cursor-not-allowed",
                    )}
                  />
                  <div className="absolute right-3 flex items-center pointer-events-none">
                    <ChevronDown className="w-4 h-4 text-gray-400" />
                  </div>
                </div>
                {/* モジュール選択ドロップダウン（Portal経由でbody直下にレンダリング） */}
                {renderModuleDropdown()}
              </div>
            )}

            {/* 検索入力 + 検索ボタン（常に横並び） */}
            <div className="flex items-center gap-2 flex-1 min-w-0">
              {/* 入力フィールド部分 */}
              <div className="relative flex-1" ref={inputContainerRef}>
                <div className="relative flex items-center">
                  <Input
                    ref={inputRef}
                    id={`field_${name}`}
                    type="text"
                    value={displayLabel}
                    onChange={handleSearchChange}
                    onFocus={handleFocus}
                    onKeyDown={handleSearchKeyDown}
                    onBlur={() => {
                      // Tab離脱等の blur でドロップダウンを閉じる
                      // 候補クリック時の選択処理を妨げないよう150ms遅延
                      setTimeout(() => setIsOpen(false), 150);
                    }}
                    disabled={disabled}
                    placeholder={t("LBL_PLACEHOLDER_SEARCH", label)}
                    autoComplete="off"
                    className={cn("pr-10", error && "border-red-500")}
                  />

                  {/* クリアボタン or ローディング */}
                  <div className="absolute right-3 flex items-center">
                    {isLoading ? (
                      <Loader2 className="w-4 h-4 text-gray-400 animate-spin" />
                    ) : displayLabel ? (
                      <button
                        type="button"
                        onClick={handleClear}
                        disabled={disabled}
                        className="p-1 hover:bg-gray-100 rounded"
                      >
                        <X className="w-4 h-4 text-gray-400" />
                      </button>
                    ) : (
                      <ChevronDown className="w-4 h-4 text-gray-400" />
                    )}
                  </div>
                </div>

                {/* 検索結果ドロップダウン（Portal経由でbody直下にレンダリング） */}
                {renderSearchDropdown()}
              </div>

              {/* Drawer検索ボタン */}
              <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => setIsDrawerOpen(true)}
                disabled={disabled}
                className="flex-shrink-0"
              >
                <Search className="w-4 h-4" />
              </Button>
            </div>
          </div>

          {/* フィールド条件検索Drawer */}
          <RecordSearchDrawer
            open={isDrawerOpen}
            onOpenChange={setIsDrawerOpen}
            moduleName={selectedModule}
            title={t("LBL_PLACEHOLDER_SEARCH_TITLE", label)}
            onSelect={handleDrawerSelect}
            selectedId={value}
          />
        </div>

        {/* 隠しフィールド（実際の値） */}
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

export default ReferenceField;
