import React, { useState, useMemo, useRef, useEffect, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { Input } from './ui/input';
import { X, ChevronDown } from 'lucide-react';
import { cn } from '../lib/utils';
import { useOptionalTranslation } from '../hooks/useTranslation';

/**
 * ピックリストオプションの型
 */
interface PicklistOption {
  value: string;
  label: string;
}

/**
 * PicklistFieldのProps
 */
export interface PicklistFieldProps {
  /** フィールド名 */
  name: string;
  /** ラベル */
  label: string;
  /** 現在の値 */
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
  /** 空白選択を許可しない（UIType 16） */
  noBlank?: boolean;
  /** RecordTypeフィールドかどうか */
  isRecordTypeField?: boolean;
  /** RecordType変更時のコールバック */
  onRecordTypeChange?: (fieldName: string, value: string) => void;
}

/**
 * PicklistField - ピックリストフィールド（UIType 15/16）用コンポーネント
 * 検索可能なドロップダウン
 */
export const PicklistField: React.FC<PicklistFieldProps> = ({
  name,
  label,
  value,
  onChange,
  options = [],
  mandatory = false,
  disabled = false,
  error,
  className,
  noBlank = false,
  isRecordTypeField = false,
  onRecordTypeChange
}) => {
  // 翻訳フック（TranslationProvider外でも安全に使用可能）
  const { t } = useOptionalTranslation();

  // 検索キーワード
  const [searchTerm, setSearchTerm] = useState<string>('');

  // 表示用のラベル
  const [displayLabel, setDisplayLabel] = useState<string>('');

  // ドロップダウン表示状態
  const [isOpen, setIsOpen] = useState<boolean>(false);

  // 参照
  const inputRef = useRef<HTMLInputElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const inputContainerRef = useRef<HTMLDivElement>(null);

  // ドロップダウンの位置
  const [dropdownPosition, setDropdownPosition] = useState<{ top: number; left: number; width: number } | null>(null);

  /**
   * 検索でフィルタリングされたオプション
   */
  const filteredOptions = useMemo(() => {
    if (!searchTerm) return options;
    const lowerSearch = searchTerm.toLowerCase();
    return options.filter(opt =>
      opt.label.toLowerCase().includes(lowerSearch) ||
      opt.value.toLowerCase().includes(lowerSearch)
    );
  }, [options, searchTerm]);

  /**
   * 現在の値から表示ラベルを設定
   */
  useEffect(() => {
    if (value) {
      const selected = options.find(opt => opt.value === value);
      if (selected) {
        setDisplayLabel(selected.label);
      }
    } else {
      setDisplayLabel('');
    }
  }, [value, options]);

  /**
   * 検索入力変更
   */
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setSearchTerm(newValue);
    setDisplayLabel(newValue);

    // 既存の選択をクリア
    if (value) {
      onChange(name, '');
    }

    // ドロップダウンを開く
    setIsOpen(true);
  };

  /**
   * オプション選択
   */
  const handleSelectOption = (option: PicklistOption) => {
    setDisplayLabel(option.label);
    setSearchTerm('');
    setIsOpen(false);
    onChange(name, option.value);

    // RecordTypeフィールドの場合、追加で親コンポーネントに通知
    if (isRecordTypeField && onRecordTypeChange) {
      onRecordTypeChange(name, option.value);
    }
  };

  /**
   * 選択をクリア
   */
  const handleClear = () => {
    setDisplayLabel('');
    setSearchTerm('');
    onChange(name, '');
    inputRef.current?.focus();
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
        width: rect.width
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
        // 選択されていない場合は入力をクリア
        if (!value && displayLabel) {
          setDisplayLabel('');
          setSearchTerm('');
        }
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [value, displayLabel]);

  /**
   * スクロール・リサイズ時にドロップダウンの位置を更新
   */
  useEffect(() => {
    if (!isOpen) return;

    const handleScrollOrResize = () => {
      updateDropdownPosition();
    };

    window.addEventListener('scroll', handleScrollOrResize, true);
    window.addEventListener('resize', handleScrollOrResize);

    return () => {
      window.removeEventListener('scroll', handleScrollOrResize, true);
      window.removeEventListener('resize', handleScrollOrResize);
    };
  }, [isOpen, updateDropdownPosition]);

  // クリアボタンを表示するかどうか（noBlankの場合は表示しない）
  const showClearButton = displayLabel && !noBlank && !mandatory;

  // ドロップダウンのレンダリング（Portal使用）
  const renderDropdown = () => {
    if (!isOpen || disabled || !dropdownPosition) return null;

    const dropdown = (
      <div
        ref={dropdownRef}
        className="fixed z-[100003] bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto pointer-events-auto"
        style={{
          top: dropdownPosition.top,
          left: dropdownPosition.left,
          width: dropdownPosition.width
        }}
        onWheel={(e) => {
          e.stopPropagation();
          // Radix UIのDialogがbodyのスクロールをブロックするため、手動でスクロール
          const target = e.currentTarget;
          target.scrollTop += e.deltaY * 0.3;
        }}
      >
        {filteredOptions.length > 0 ? (
          <div className="py-1">
            {filteredOptions.map(option => (
              <div
                key={option.value}
                onClick={() => handleSelectOption(option)}
                className={cn(
                  'px-3 py-1.5 text-md cursor-pointer hover:bg-blue-50',
                  value === option.value && 'bg-blue-100'
                )}
              >
                {option.label}
              </div>
            ))}
          </div>
        ) : (
          <div className="px-3 py-1.5 text-md text-gray-500 text-center">
            該当する選択肢がありません
          </div>
        )}
      </div>
    );

    return createPortal(dropdown, document.body);
  };

  return (
    <div className={cn('flex items-start gap-2', className)}>
      {/* ラベル（旧版スタイル：右寄せ） */}
      <span
        className={cn(
          'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]',
          disabled && 'text-gray-400'
        )}
      >
        {label}
        {mandatory && <span className="sr-only"> (必須)</span>}
      </span>
      {/* 必須マーク：固定幅で位置を確保し、入力欄の開始位置を揃える */}
      <span className="w-3 leading-[30px] text-red-500 text-center flex-shrink-0" aria-hidden="true">
        {mandatory ? '*' : ''}
      </span>

      {/* 入力エリア */}
      <div className="flex-1 min-w-0">
        {/* 検索入力 */}
        <div className="relative" ref={inputContainerRef}>
        <div className="relative flex items-center">
          <Input
            ref={inputRef}
            id={`field_${name}`}
            type="text"
            value={displayLabel}
            onChange={handleSearchChange}
            onFocus={handleFocus}
            disabled={disabled}
            placeholder={t('LBL_PLACEHOLDER_SEARCH', label)}
            autoComplete="off"
            className={cn(
              'pr-10',
              error && 'border-red-500',
              isRecordTypeField && 'border-blue-300 bg-blue-50/30'
            )}
          />

          {/* クリアボタン or ドロップダウンアイコン */}
          <div className="absolute right-3 flex items-center">
            {showClearButton ? (
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

        {/* ドロップダウン（Portal経由でbody直下にレンダリング） */}
        {renderDropdown()}
        </div>

        {/* 隠しフィールド */}
        <input type="hidden" name={name} value={value || ''} />

        {/* エラーメッセージ */}
        {error && (
          <div className="mt-1 text-sm text-red-600 flex items-center">
            <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
            {error}
          </div>
        )}
      </div>
    </div>
  );
};

export default PicklistField;
