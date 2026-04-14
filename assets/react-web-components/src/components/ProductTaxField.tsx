import React, { useState, useEffect, useCallback } from 'react';
import { Checkbox } from './ui/checkbox';
import { Input } from './ui/input';

/**
 * ProductTaxFieldコンポーネントのProps
 */
export interface ProductTaxFieldProps {
  /** フィールド名 */
  name: string;
  /** ラベル（FieldRendererで表示するため、ここでは未使用） */
  label: string;
  /** デフォルト税率 */
  defaultTaxRate: string;
  /** 現在の値（税率文字列 or 空文字） */
  value: string;
  /** 値変更時のコールバック */
  onChange: (name: string, value: string) => void;
  /** 無効化状態 */
  disabled?: boolean;
  /** エラーメッセージ */
  error?: string;
  /** カスタムクラス名 */
  className?: string;
}

/**
 * 製品税フィールドコンポーネント
 *
 * 旧版QuickCreateと同じUI: チェックボックス + 税率入力フィールド
 * - チェックON: デフォルト税率を設定し、入力フィールドを表示
 * - チェックOFF: 空文字を設定し、入力フィールドを非表示
 */
export const ProductTaxField: React.FC<ProductTaxFieldProps> = ({
  name,
  defaultTaxRate,
  value,
  onChange,
  disabled = false,
  error,
  className = '',
}) => {
  // チェック状態（valueが存在すればチェック済み）
  const [isChecked, setIsChecked] = useState<boolean>(!!value && value !== '');
  // ローカルの表示値（チェックON直後にdefaultTaxRateを表示するため）
  const [localValue, setLocalValue] = useState<string>(value || '');
  // フォーカス状態（編集中は生の値を表示、フォーカスアウト時にフォーマット）
  const [isFocused, setIsFocused] = useState<boolean>(false);

  // valueの変更に応じてチェック状態とローカル値を同期
  useEffect(() => {
    setIsChecked(!!value && value !== '');
    setLocalValue(value || '');
  }, [value]);

  // 税率入力の変更ハンドラ
  const handleInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const newValue = e.target.value;
      setLocalValue(newValue);
      onChange(name, newValue);
    },
    [name, onChange]
  );

  // フォーカスアウト時のハンドラ（小数点3桁にフォーマット）
  const handleBlur = useCallback(() => {
    setIsFocused(false);
    if (localValue) {
      const parsed = parseFloat(localValue);
      if (!isNaN(parsed)) {
        const formatted = parsed.toFixed(3);
        setLocalValue(formatted);
        onChange(name, formatted);
      }
    }
  }, [localValue, name, onChange]);

  // フォーカス時のハンドラ
  const handleFocus = useCallback(() => {
    setIsFocused(true);
  }, []);

  // 表示値：フォーカス中は生の値、フォーカス外は小数点3桁フォーマット
  const displayValue = isFocused
    ? localValue
    : (localValue ? parseFloat(localValue).toFixed(3) : '0.000');

  return (
    <div className={`product-tax-field flex items-center gap-2 w-full ${className}`}>
      {/* チェックボックス（既存のCheckboxコンポーネントを使用） */}
      <Checkbox
        id={`${name}_check`}
        checked={isChecked}
        onCheckedChange={(checked) => {
          const newChecked = checked === true;
          setIsChecked(newChecked);
          if (newChecked) {
            setLocalValue(defaultTaxRate);
            onChange(name, defaultTaxRate);
          } else {
            setLocalValue('');
            onChange(name, '');
          }
        }}
        disabled={disabled}
      />

      {/* 税率入力フィールド（チェック時のみ表示） */}
      {isChecked && (
        <Input
          type="text"
          inputMode="decimal"
          id={name}
          name={name}
          value={displayValue}
          onChange={handleInputChange}
          onFocus={handleFocus}
          onBlur={handleBlur}
          disabled={disabled}
          className={`flex-1 ${error ? 'border-red-500' : ''}`}
        />
      )}
    </div>
  );
};

export default ProductTaxField;
