import React from 'react';
import { FieldRendererProps, UI_TYPES, FieldValue, HTMLInputValue } from '../types/field';
import { Input } from './ui/input';
import { Textarea } from './ui/textarea';
import { CheckboxSwitch } from './ui/checkbox-switch';
import { ReferenceField } from './ReferenceField';
import { MultireferenceField } from './MultireferenceField';
import { OwnerField } from './OwnerField';
import { PicklistField } from './PicklistField';
import { cn } from '../lib/utils';
import { useOptionalTranslation } from '../hooks/useTranslation';

/**
 * FieldValueをHTMLInput要素に渡せる型に変換
 */
const toInputValue = (value: FieldValue): HTMLInputValue => {
  if (value === null || value === undefined) return '';
  if (typeof value === 'boolean') return value ? '1' : '';
  if (Array.isArray(value)) return value;
  return value;
};

/**
 * FieldRenderer - UITypeに応じた適切なコンポーネントを選択・レンダリングするコンポーネント
 * 既存のUIコンポーネント（Input, Textarea, CheckboxSwitch等）を活用
 */
export const FieldRenderer: React.FC<FieldRendererProps> = ({
  field,
  value,
  onChange,
  disabled = false,
  error,
  className,
  onRecordTypeChange,
  formData
}) => {
  // 翻訳フック（TranslationProvider外でも安全に使用可能）
  const { t } = useOptionalTranslation();

  // エラーハンドリング（早期リターン）
  if (!field) {
    return (
      <div className="text-red-600 p-2 border border-red-300 rounded">
        エラー: フィールド情報が見つかりません
      </div>
    );
  }

  if (!field.uitype) {
    return (
      <div className="text-yellow-600 p-2 border border-yellow-300 rounded">
        警告: UITypeが指定されていません (フィールド: {field.name})
      </div>
    );
  }

  // 共通のイベントハンドラー
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    onChange(field.name, e.target.value);
  };

  // datetime-local/time用のイベントハンドラー（秒を除去）
  const handleDateTimeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    let val = e.target.value;
    // 秒が含まれている場合は除去（YYYY-MM-DDTHH:MM:SS → YYYY-MM-DDTHH:MM）
    // または時刻の場合（HH:MM:SS → HH:MM）
    if (val) {
      // datetime-local: 2025-12-18T15:30:00 → 2025-12-18T15:30
      // time: 15:30:00 → 15:30
      const parts = val.split(':');
      if (parts.length === 3) {
        val = `${parts[0]}:${parts[1]}`;
      }
    }
    onChange(field.name, val);
  };

  // ピックリスト用のオプション変換
  const picklistOptions = field.picklistValues?.map(option => ({
    value: option.value,
    label: option.label
  }));

  // エラーメッセージのID（アクセシビリティ用）
  const errorId = error ? `error_${field.name}` : undefined;

  // ラベル要素の共通レンダリング（横並びレイアウト用・旧版スタイル：ラベル右寄せ）
  // ラベル部分と必須マークを分離して、ラベル終端を揃える
  const renderLabel = () => (
    <>
      <label
        htmlFor={`field_${field.name}`}
        className={cn(
          'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]',
          disabled && 'text-gray-400'
        )}
      >
        {field.label}
        {field.mandatory && <span className="sr-only"> (必須)</span>}
      </label>
      {/* 必須マーク：固定幅で位置を確保し、入力欄の開始位置を揃える */}
      <span className="w-3 leading-[30px] text-red-500 text-center flex-shrink-0" aria-hidden="true">
        {field.mandatory ? '*' : ''}
      </span>
    </>
  );

  // エラーメッセージの共通レンダリング（アクセシビリティ対応）
  const renderError = () => error && (
    <div
      id={errorId}
      role="alert"
      aria-live="polite"
      className="mt-1 text-sm text-red-600 flex items-center"
    >
      <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
      </svg>
      {error}
    </div>
  );

  /**
   * UITypeに応じたコンポーネントを選択
   */
  const renderFieldByUIType = (uitype: string) => {
    const inputProps = {
      id: `field_${field.name}`,
      name: field.name,
      value: toInputValue(value) || '',
      disabled: disabled || field.readonly,
      className: cn('w-full', error && 'border-red-500'),
      placeholder: field.readonly ? '' : t('LBL_PLACEHOLDER_ENTER', field.label),
      'aria-describedby': errorId,
      'aria-invalid': !!error,
      'aria-required': field.mandatory
    };

    // multireference判定（参照フィールドより先に判定）
    // datatype が 'multireference' または isMultiple が true の場合、MultireferenceFieldを使用
    if (field.datatype === 'multireference' || field.isMultiple) {
      // Get display values for multireference field (e.g., contact_id_display)
      const multirefDisplayValue = formData?.[`${field.name}_display`];
      // Convert display value to array format expected by MultireferenceField
      let multirefDisplayValues: Array<{ id: string; label: string }> | undefined;
      if (multirefDisplayValue && value) {
        const ids = String(value).split(';').filter(id => id.trim());
        const labels = String(multirefDisplayValue).split(';');
        multirefDisplayValues = ids.map((id, index) => ({
          id: id.trim(),
          label: labels[index]?.trim() || `ID: ${id.trim()}`
        }));
      }
      return (
        <MultireferenceField
          name={field.name}
          label={field.label}
          referenceModules={field.referenceModules || []}
          referenceModuleLabels={field.referenceModuleLabels}
          value={String(value ?? '')}
          displayValues={multirefDisplayValues}
          onChange={onChange}
          mandatory={field.mandatory}
          disabled={disabled || field.readonly}
          error={error}
          className={className}
        />
      );
    }

    switch (uitype) {
      // 文字列系 - Input
      case UI_TYPES.STRING:
      case UI_TYPES.STRING_LONG:
      case UI_TYPES.STRING_106:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="text"
                onChange={handleInputChange}
                maxLength={field.maxlength}
              />
              {renderError()}
            </div>
          </div>
        );

      // テキストエリア系 - Textarea
      // pt-[7px]でラベル（leading-[30px]）の1行目と縦位置を揃える
      case UI_TYPES.TEXTAREA:
      case UI_TYPES.TEXTAREA_LONG:
      case UI_TYPES.TEXTAREA_20:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Textarea
                {...inputProps}
                onChange={handleInputChange}
                rows={uitype === UI_TYPES.TEXTAREA_LONG ? 6 : 3}
                className={cn(inputProps.className, 'pt-[7px]')}
              />
              {renderError()}
            </div>
          </div>
        );
      
      // 数値系 - Input type="number"
      case UI_TYPES.NUMBER:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="number"
                onChange={handleInputChange}
                step="1"
              />
              {renderError()}
            </div>
          </div>
        );

      // 小数 - Input type="number" with decimal
      case UI_TYPES.DECIMAL:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="number"
                step="0.01"
                onChange={handleInputChange}
              />
              {renderError()}
            </div>
          </div>
        );
      
      // 真偽値系 - CheckboxSwitch
      case UI_TYPES.BOOLEAN:
        // CheckboxSwitchは"1"/"0"文字列を期待する
        const boolValue = value === true || value === '1' || value === 1 ? '1' : '0';
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0 h-[30px] flex items-center">
              <CheckboxSwitch
                name={field.name}
                value={boolValue}
                disabled={disabled || field.readonly}
                className="w-auto"
                onChange={(e) => onChange(field.name, e.target.value)}
              />
              {renderError()}
            </div>
          </div>
        );
      
      // Email - Input type="email"
      case UI_TYPES.EMAIL:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="email"
                onChange={handleInputChange}
                placeholder="user@example.com"
              />
              {renderError()}
            </div>
          </div>
        );
      
      // 電話番号 - Input type="tel"
      case UI_TYPES.PHONE:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="tel"
                onChange={handleInputChange}
                placeholder="03-1234-5678"
              />
              {renderError()}
            </div>
          </div>
        );
      
      // URL - Input type="url"
      case UI_TYPES.URL:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="url"
                onChange={handleInputChange}
                placeholder="https://example.com"
              />
              {renderError()}
            </div>
          </div>
        );

      // 日付 - Input type="date"
      case UI_TYPES.DATE:
      case UI_TYPES.DATE_23:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="date"
                onChange={handleInputChange}
              />
              {renderError()}
            </div>
          </div>
        );

      // 日時 - Input type="datetime-local"
      // step="60" で分単位選択（秒非表示）、handleDateTimeChangeで秒を除去
      case UI_TYPES.DATETIME_CALENDAR:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="datetime-local"
                step="60"
                onChange={handleDateTimeChange}
              />
              {renderError()}
            </div>
          </div>
        );

      // 時刻 - Input type="time"
      // step="60" で分単位選択（秒非表示）、handleDateTimeChangeで秒を除去
      case UI_TYPES.TIME:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="time"
                step="60"
                onChange={handleDateTimeChange}
              />
              {renderError()}
            </div>
          </div>
        );
      
      // 担当者 - OwnerField
      case UI_TYPES.OWNER:
        return (
          <OwnerField
            name={field.name}
            label={field.label}
            value={String(value ?? '')}
            onChange={onChange}
            mandatory={field.mandatory}
            disabled={disabled || field.readonly}
            error={error}
            className={className}
            fieldinfo={field.fieldinfo}
          />
        );

      // ピックリスト系 - PicklistField（検索可能なドロップダウン）
      case UI_TYPES.PICKLIST:
      case UI_TYPES.PICKLIST_NO_BLANK:
        return (
          <PicklistField
            name={field.name}
            label={field.label}
            value={String(value ?? '')}
            onChange={onChange}
            options={picklistOptions}
            mandatory={field.mandatory}
            disabled={disabled || field.readonly}
            error={error}
            className={className}
            noBlank={uitype === UI_TYPES.PICKLIST_NO_BLANK}
            isRecordTypeField={field.isRecordTypeField}
            onRecordTypeChange={onRecordTypeChange}
          />
        );

      // マルチピックリスト - 複数選択select
      case UI_TYPES.MULTIPICKLIST:
        // valueは ' |##| ' 区切りの文字列か配列
        const multiValue = typeof value === 'string' ? value.split(' |##| ').filter(Boolean) : (Array.isArray(value) ? value : []);
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <select
                id={`field_${field.name}`}
                name={field.name}
                value={multiValue}
                onChange={(e) => {
                  const selectedOptions = Array.from(e.target.selectedOptions, option => option.value);
                  onChange(field.name, selectedOptions.join(' |##| '));
                }}
                disabled={disabled || field.readonly}
                multiple
                className={cn(
                  'w-full px-3 py-2 border rounded-md shadow-sm transition-colors min-h-20',
                  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                  'disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed',
                  error ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'
                )}
              >
                {picklistOptions?.map(option => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              <div className="text-xs text-gray-500">
                Ctrlキーを押しながらクリックして複数選択
              </div>
              {renderError()}
            </div>
          </div>
        );
      
      // 通貨 - Input type="number" with currency formatting
      case UI_TYPES.CURRENCY:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <div className="relative">
                <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">¥</span>
                <Input
                  {...inputProps}
                  type="number"
                  step="0.01"
                  onChange={handleInputChange}
                  className={cn(inputProps.className, 'pl-8')}
                  placeholder="0.00"
                />
              </div>
              {renderError()}
            </div>
          </div>
        );

      // パーセンテージ - Input type="number" with % suffix
      case UI_TYPES.PERCENTAGE:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <div className="relative">
                <Input
                  {...inputProps}
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  onChange={handleInputChange}
                  className={cn(inputProps.className, 'pr-8')}
                  placeholder="0"
                />
                <span className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
              </div>
              {renderError()}
            </div>
          </div>
        );

      // 敬称 - PicklistFieldを使用（ピックリストと同様の挙動）
      case UI_TYPES.SALUTATION:
        return (
          <PicklistField
            name={field.name}
            label={field.label}
            value={String(value ?? '')}
            onChange={onChange}
            options={picklistOptions}
            mandatory={field.mandatory}
            disabled={disabled || field.readonly}
            error={error}
            className={className}
            noBlank={false}
          />
        );

      // パスワード - Input type="password"
      case UI_TYPES.PASSWORD:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="password"
                onChange={handleInputChange}
                placeholder="••••••••"
                autoComplete="new-password"
              />
              {renderError()}
            </div>
          </div>
        );

      // 参照フィールド - ReferenceField
      // UIType 10, 51, 52, 57, 58, 59, 66, 73, 75, 76, 77, 78, 80, 81, 101
      case UI_TYPES.REFERENCE:
      case UI_TYPES.REFERENCE_ACCOUNT:
      case UI_TYPES.REFERENCE_USER:
      case UI_TYPES.REFERENCE_CONTACT:
      case UI_TYPES.REFERENCE_CAMPAIGN:
      case UI_TYPES.REFERENCE_PRODUCT:
      case UI_TYPES.REFERENCE_RELATED:
      case UI_TYPES.REFERENCE_ACCOUNT2:
      case UI_TYPES.REFERENCE_VENDOR:
      case UI_TYPES.REFERENCE_POTENTIAL:
      case UI_TYPES.REFERENCE_77:
      case UI_TYPES.REFERENCE_QUOTE:
      case UI_TYPES.REFERENCE_SALESORDER:
      case UI_TYPES.REFERENCE_PURCHASEORDER:
      case UI_TYPES.REFERENCE_USER2:
        // Get display value for reference field (e.g., contact_id_display)
        const referenceDisplayValue = formData?.[`${field.name}_display`];
        return (
          <ReferenceField
            name={field.name}
            label={field.label}
            referenceModules={field.referenceModules || []}
            referenceModuleLabels={field.referenceModuleLabels}
            value={String(value ?? '')}
            displayValue={referenceDisplayValue ? String(referenceDisplayValue) : undefined}
            onChange={onChange}
            mandatory={field.mandatory}
            disabled={disabled || field.readonly}
            error={error}
            className={className}
          />
        );

      // 未対応のUIType - 文字列入力として扱う
      default:
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderLabel()}
            <div className="flex-1 min-w-0">
              <Input
                {...inputProps}
                type="text"
                onChange={handleInputChange}
              />
              {renderError()}
            </div>
          </div>
        );
    }
  };

  // エラーハンドリング
  if (!field) {
    return (
      <div className="text-red-600 p-2 border border-red-300 rounded">
        エラー: フィールド情報が見つかりません
      </div>
    );
  }

  if (!field.uitype) {
    return (
      <div className="text-yellow-600 p-2 border border-yellow-300 rounded">
        警告: UITypeが指定されていません (フィールド: {field.name})
      </div>
    );
  }

  try {
    return (
      <div className="field-renderer">
        {renderFieldByUIType(field.uitype)}
      </div>
    );
  } catch (error) {
    console.error('FieldRenderer エラー:', error, { field });
    return (
      <div className="text-red-600 p-2 border border-red-300 rounded">
        エラー: フィールドの表示に失敗しました
        <br />
        <small>フィールド: {field.name} (UIType: {field.uitype})</small>
      </div>
    );
  }
};

/**
 * UITypeに対応したコンポーネントが実装されているかチェック
 */
export const isUITypeSupported = (uitype: string): boolean => {
  const supportedUITypes = [
    // 文字列系
    UI_TYPES.STRING,
    UI_TYPES.STRING_LONG,
    UI_TYPES.STRING_106,
    // テキストエリア系
    UI_TYPES.TEXTAREA,
    UI_TYPES.TEXTAREA_LONG,
    UI_TYPES.TEXTAREA_20,
    // 数値系
    UI_TYPES.NUMBER,
    UI_TYPES.DECIMAL,
    UI_TYPES.CURRENCY,
    UI_TYPES.PERCENTAGE,
    // 日時系
    UI_TYPES.DATE,
    UI_TYPES.DATE_23,
    UI_TYPES.DATETIME_CALENDAR,
    UI_TYPES.TIME,
    // 選択系
    UI_TYPES.PICKLIST,
    UI_TYPES.PICKLIST_NO_BLANK,
    UI_TYPES.MULTIPICKLIST,
    UI_TYPES.SALUTATION,
    // 真偽値
    UI_TYPES.BOOLEAN,
    // 連絡先系
    UI_TYPES.EMAIL,
    UI_TYPES.PHONE,
    UI_TYPES.URL,
    // 担当者
    UI_TYPES.OWNER,
    // パスワード
    UI_TYPES.PASSWORD,
    // 参照系
    UI_TYPES.REFERENCE,
    UI_TYPES.REFERENCE_ACCOUNT,
    UI_TYPES.REFERENCE_USER,
    UI_TYPES.REFERENCE_CONTACT,
    UI_TYPES.MULTIREFERENCE,
    UI_TYPES.REFERENCE_CAMPAIGN,
    UI_TYPES.REFERENCE_PRODUCT,
    UI_TYPES.REFERENCE_RELATED,
    UI_TYPES.REFERENCE_ACCOUNT2,
    UI_TYPES.REFERENCE_VENDOR,
    UI_TYPES.REFERENCE_POTENTIAL,
    UI_TYPES.REFERENCE_77,
    UI_TYPES.REFERENCE_QUOTE,
    UI_TYPES.REFERENCE_SALESORDER,
    UI_TYPES.REFERENCE_PURCHASEORDER,
    UI_TYPES.REFERENCE_USER2,
  ];

  return supportedUITypes.includes(uitype as any);
};

/**
 * 利用可能なUITypeの一覧を取得
 */
export const getSupportedUITypes = (): string[] => {
  return [
    // 文字列系
    UI_TYPES.STRING,
    UI_TYPES.STRING_LONG,
    UI_TYPES.STRING_106,
    // テキストエリア系
    UI_TYPES.TEXTAREA,
    UI_TYPES.TEXTAREA_LONG,
    UI_TYPES.TEXTAREA_20,
    // 数値系
    UI_TYPES.NUMBER,
    UI_TYPES.DECIMAL,
    UI_TYPES.CURRENCY,
    UI_TYPES.PERCENTAGE,
    // 日時系
    UI_TYPES.DATE,
    UI_TYPES.DATE_23,
    UI_TYPES.DATETIME_CALENDAR,
    UI_TYPES.TIME,
    // 選択系
    UI_TYPES.PICKLIST,
    UI_TYPES.PICKLIST_NO_BLANK,
    UI_TYPES.MULTIPICKLIST,
    UI_TYPES.SALUTATION,
    // 真偽値
    UI_TYPES.BOOLEAN,
    // 連絡先系
    UI_TYPES.EMAIL,
    UI_TYPES.PHONE,
    UI_TYPES.URL,
    // 担当者
    UI_TYPES.OWNER,
    // パスワード
    UI_TYPES.PASSWORD,
    // 参照系
    UI_TYPES.REFERENCE,
    UI_TYPES.REFERENCE_ACCOUNT,
    UI_TYPES.REFERENCE_USER,
    UI_TYPES.REFERENCE_CONTACT,
    UI_TYPES.MULTIREFERENCE,
    UI_TYPES.REFERENCE_CAMPAIGN,
    UI_TYPES.REFERENCE_PRODUCT,
    UI_TYPES.REFERENCE_RELATED,
    UI_TYPES.REFERENCE_ACCOUNT2,
    UI_TYPES.REFERENCE_VENDOR,
    UI_TYPES.REFERENCE_POTENTIAL,
    UI_TYPES.REFERENCE_77,
    UI_TYPES.REFERENCE_QUOTE,
    UI_TYPES.REFERENCE_SALESORDER,
    UI_TYPES.REFERENCE_PURCHASEORDER,
    UI_TYPES.REFERENCE_USER2,
  ];
};

export default FieldRenderer;