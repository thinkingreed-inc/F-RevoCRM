import React, { useEffect, useRef } from 'react';
import { FieldRendererProps, UI_TYPES, FieldValue, HTMLInputValue } from '../types/field';
import { Input } from './ui/input';
import { Textarea } from './ui/textarea';
import { CheckboxSwitch } from './ui/checkbox-switch';
import { ReferenceField } from './ReferenceField';
import { MultireferenceField } from './MultireferenceField';
import { OwnerField } from './OwnerField';
import { PicklistField } from './PicklistField';
import { MultiPicklistField } from './MultiPicklistField';
import { ProductTaxField } from './ProductTaxField';
import { CurrencyListField } from './CurrencyListField';
import { cn } from '../lib/utils';
import { useOptionalTranslation } from '../hooks/useTranslation';

declare global {
  interface Window {
    jQuery?: any;
    Vtiger_Jodit_Js?: any;
  }
}

/**
 * FieldValueをHTMLInput要素に渡せる型に変換
 */
const toInputValue = (value: FieldValue): HTMLInputValue => {
  if (value === null || value === undefined) return '';
  if (typeof value === 'boolean') return value ? '1' : '';
  if (Array.isArray(value)) return value;
  return value;
};

const QUICKCREATE_EDITOR_MAX_HEIGHT = '200px';

type JoditEditorTextareaProps = {
  id: string;
  name: string;
  value: string;
  disabled?: boolean;
  className?: string;
  rows?: number;
  onChange: (name: string, value: string) => void;
};

const JoditEditorTextarea: React.FC<JoditEditorTextareaProps> = ({
  id,
  name,
  value,
  disabled = false,
  className,
  rows = 3,
  onChange
}) => {
  const textareaRef = useRef<HTMLTextAreaElement | null>(null);
  const wrapperRef = useRef<any>(null);
  const onChangeRef = useRef(onChange);

  useEffect(() => {
    onChangeRef.current = onChange;
  }, [onChange]);

  useEffect(() => {
    const textarea = textareaRef.current;
    const jQuery = window.jQuery;
    const JoditEditor = window.Vtiger_Jodit_Js;

    if (!textarea || !jQuery || !JoditEditor) {
      return;
    }

    const element = jQuery(textarea);
    element.removeAttr('data-validation-engine').addClass('joditEditorSource');

    const joditInstance = new JoditEditor();
    joditInstance.loadJoditEditor(element);

    const wrapper = JoditEditor.getInstance(id);
    wrapperRef.current = wrapper;

    const container = wrapper?.jodit?.container as HTMLElement | undefined;
    if (container && window.innerWidth >= 768) {
      const wysiwyg = container.querySelector<HTMLElement>('.jodit-wysiwyg');
      if (wysiwyg) {
        wysiwyg.style.maxHeight = QUICKCREATE_EDITOR_MAX_HEIGHT;
        wysiwyg.style.overflowY = 'auto';
      }
    }

    const emitChange = () => {
      const currentWrapper = wrapperRef.current;
      const nextValue = currentWrapper && typeof currentWrapper.getData === 'function'
        ? currentWrapper.getData()
        : String(element.val() || '');
      onChangeRef.current(name, nextValue);
    };

    if (wrapper?.jodit?.events) {
      wrapper.jodit.events.on('change', emitChange);
      wrapper.jodit.events.on('blur', emitChange);
    }

    return () => {
      if (wrapper?.jodit?.events) {
        wrapper.jodit.events.off('change', emitChange);
        wrapper.jodit.events.off('blur', emitChange);
      }
      if (wrapper && typeof wrapper.destroy === 'function') {
        wrapper.destroy();
      }
      wrapperRef.current = null;
    };
  }, [id, name]);

  useEffect(() => {
    const nextValue = value || '';
    const wrapper = wrapperRef.current;

    if (wrapper && typeof wrapper.getData === 'function' && typeof wrapper.setData === 'function') {
      if (wrapper.getData() !== nextValue) {
        wrapper.setData(nextValue);
      }
      return;
    }

    if (textareaRef.current && textareaRef.current.value !== nextValue) {
      textareaRef.current.value = nextValue;
    }
  }, [value]);

  return (
    <textarea
      ref={textareaRef}
      id={id}
      name={name}
      rows={rows}
      defaultValue={value}
      disabled={disabled}
      className={className}
      style={{ height: '250px', maxWidth: 'initial', width: '100%' }}
    />
  );
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
  labelClassName,
  onRecordTypeChange,
  formData,
  module
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
  const joditEditorId = `${module || 'QuickCreate'}_quickCreate_fieldName_${field.name}`;

  const renderLabel = () => {
    if (labelClassName) {
      // モバイル縦並び時：* をラベルテキスト直後にインライン配置
      // md:contents でデスクトップ時は div が消え、子要素が親 flex に直接参加する
      return (
        <div className="flex items-baseline md:contents">
          <span
            className={cn(
              'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]',
              disabled && 'text-gray-400',
              labelClassName
            )}
          >
            {field.label}
            {field.mandatory && <span className="text-red-500" aria-hidden="true">*</span>}
            {field.mandatory && <span className="sr-only"> (必須)</span>}
          </span>
          {/* デスクトップ時に入力開始位置を揃えるスペーサー（モバイルは非表示） */}
          <span className="w-3 flex-shrink-0 hidden md:block" aria-hidden="true" />
        </div>
      );
    }
    return (
      <>
        <span
          className={cn(
            'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]',
            disabled && 'text-gray-400'
          )}
        >
          {field.label}
          {field.mandatory && <span className="sr-only"> (必須)</span>}
        </span>
        <span
          className="w-3 leading-[30px] text-red-500 text-center flex-shrink-0"
          aria-hidden="true"
        >
          {field.mandatory ? '*' : ''}
        </span>
      </>
    );
  };

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
              {field.isJoditEditor ? (
                <JoditEditorTextarea
                  id={joditEditorId}
                  name={field.name}
                  value={String(value ?? '')}
                  disabled={disabled || field.readonly}
                  onChange={onChange}
                  rows={uitype === UI_TYPES.TEXTAREA_LONG ? 6 : 3}
                  className={cn(
                    'inputElement textAreaElement col-lg-12',
                    inputProps.className,
                    error && 'border-red-500'
                  )}
                />
              ) : (
                <Textarea
                  {...inputProps}
                  onChange={handleInputChange}
                  rows={uitype === UI_TYPES.TEXTAREA_LONG ? 6 : 3}
                  className={cn(inputProps.className, 'pt-[7px]')}
                />
              )}
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
            labelClassName={labelClassName}
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
            labelClassName={labelClassName}
            noBlank={uitype === UI_TYPES.PICKLIST_NO_BLANK}
            isRecordTypeField={field.isRecordTypeField}
            onRecordTypeChange={onRecordTypeChange}
          />
        );

      // マルチピックリスト - MultiPicklistField
      case UI_TYPES.MULTIPICKLIST:
        return (
          <MultiPicklistField
            name={field.name}
            label={field.label}
            value={String(value ?? '')}
            onChange={onChange}
            options={picklistOptions}
            mandatory={field.mandatory}
            disabled={disabled || field.readonly}
            error={error}
            className={className}
            labelClassName={labelClassName}
          />
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

      // 敬称/名前 (uitype 55) - テキスト入力として扱う
      // 注: uitype 55はsalutationtypeとfirstnameの両方に使用されるが、
      // salutationtypeはdisplaytype=3のためクイック作成には表示されない
      // firstnameは名前入力フィールドなのでテキスト入力が適切
      case UI_TYPES.SALUTATION:
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

      // 製品税 - ProductTaxField（チェックボックス + 税率入力）
      // 旧版と同じく、ラベル部分にtaxLabelを表示（例: 消費税(%)）
      case UI_TYPES.PRODUCT_TAX:
        // taxClassDetailsがない場合はデフォルト値を使用
        const taxDetails = field.taxClassDetails || {
          taxname: field.name,
          taxlabel: field.label + '(%)',
          percentage: '10.000',
          check_name: `check_${field.name}`,
          check_value: '0'
        };
        // ProductTaxField用のカスタムラベル（taxlabelを使用）
        const renderTaxLabel = () => (
          <>
            <span
              className={cn(
                'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]',
                (disabled || field.readonly) && 'text-gray-400'
              )}
            >
              {taxDetails.taxlabel}
              {field.mandatory && <span className="sr-only"> (必須)</span>}
            </span>
            <span className="w-3 leading-[30px] text-red-500 text-center flex-shrink-0" aria-hidden="true">
              {field.mandatory ? '*' : ''}
            </span>
          </>
        );
        return (
          <div className={cn('flex items-start gap-2', className)}>
            {renderTaxLabel()}
            <div className="flex-1 min-w-0 h-[30px] flex items-center">
              <ProductTaxField
                name={taxDetails.taxname}
                label={taxDetails.taxlabel}
                defaultTaxRate={taxDetails.percentage}
                value={String(value ?? '')}
                onChange={onChange}
                disabled={disabled || field.readonly}
                error={error}
              />
              {renderError()}
            </div>
          </div>
        );

      // 通貨リスト - CurrencyListField（UIType 117）
      case UI_TYPES.CURRENCY_LIST:
        // fieldinfo.currencyListから通貨リストを取得
        const currencyList = (field.fieldinfo?.currencyList as Record<string, string>) || {};
        return (
          <CurrencyListField
            name={field.name}
            label={field.label}
            value={String(value ?? '')}
            onChange={onChange}
            currencyList={currencyList}
            mandatory={field.mandatory}
            disabled={disabled || field.readonly}
            error={error}
            className={className}
          />
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
    // 特殊
    UI_TYPES.PRODUCT_TAX,
    UI_TYPES.CURRENCY_LIST,
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
    // 特殊
    UI_TYPES.PRODUCT_TAX,
    UI_TYPES.CURRENCY_LIST,
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
