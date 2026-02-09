import React, { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import {
  Dialog,
  DialogContent
} from '../ui/dialog';
import { Alert, AlertDescription } from '../ui/alert';
import { Loader2, XCircle, CheckCircle } from 'lucide-react';
import { QuickCreateForm } from './QuickCreateForm';
import { CalendarForm } from './CalendarForm';
import { QuickCreateFooter } from './QuickCreateFooter';
import { QuickCreateHeader } from './QuickCreateHeader';
import { useQuickCreateFields } from './hooks/useQuickCreateFields';
import { useQuickCreateSave } from './hooks/useQuickCreateSave';
import { usePicklistDependency } from './hooks/usePicklistDependency';
import { useCalendarFields } from './hooks/useCalendarFields';
import { useRecordData } from './hooks/useRecordData';
import { QuickCreateProps } from '../../types/quickcreate';
import { FieldInfo, FieldValue } from '../../types/field';
import { cn } from '../../lib/utils';
import { TranslationProvider } from '../../contexts/TranslationContext';
import { useTranslation } from '../../hooks/useTranslation';

/**
 * Activity type for Calendar variant
 */
type ActivityType = 'Calendar' | 'Events';

/**
 * Extended QuickCreate props with variant support
 */
export interface ExtendedQuickCreateProps extends QuickCreateProps {
  /** Style variant: 'default' for general modules, 'calendar' for Calendar/Events */
  variant?: 'default' | 'calendar';
  /** Record ID for edit mode */
  recordId?: string;
}

/**
 * QuickCreate - Unified quick create modal component
 *
 * Supports:
 * - General modules (default variant)
 * - Calendar/Events modules (calendar variant with tabs, datetime inputs, etc.)
 * - Edit mode (with recordId)
 *
 * @example
 * // General module
 * <QuickCreate
 *   module="Accounts"
 *   isOpen={isOpen}
 *   onSave={handleSave}
 * />
 *
 * @example
 * // Calendar/Events module
 * <QuickCreate
 *   module="Events"
 *   variant="calendar"
 *   isOpen={isOpen}
 *   onSave={handleSave}
 * />
 *
 * @example
 * // Edit mode
 * <QuickCreate
 *   module="Events"
 *   variant="calendar"
 *   recordId="12345"
 *   initialData={recordData}
 *   isOpen={isOpen}
 *   onSave={handleSave}
 * />
 */
/**
 * QuickCreate - TranslationProviderでラップしたエクスポート用コンポーネント
 *
 * TranslationProviderは指定されたmodule + Vtiger共通翻訳を取得する。
 * これにより、ユーザーがアクセス権限を持つモジュールの翻訳APIを呼び出す。
 */
export const QuickCreate: React.FC<ExtendedQuickCreateProps> = (props) => {
  return (
    <TranslationProvider module={props.module}>
      <QuickCreateInner {...props} />
    </TranslationProvider>
  );
};

/**
 * QuickCreateInner - 実際のQuickCreate実装
 */
const QuickCreateInner: React.FC<ExtendedQuickCreateProps> = ({
  module,
  variant: explicitVariant,
  isOpen: externalIsOpen,
  initialData = {},
  recordId,
  onSave,
  onCancel,
  onGoToFullForm,
  onOpenChange
}) => {
  const { t } = useTranslation();

  // Determine variant based on module if not explicitly set
  const isCalendarModule = module === 'Calendar' || module === 'Events';
  const variant = explicitVariant ?? (isCalendarModule ? 'calendar' : 'default');
  const isCalendarVariant = variant === 'calendar';
  const isEditMode = !!recordId;

  // Modal state
  const [internalIsOpen, setInternalIsOpen] = useState(false);
  const isOpen = externalIsOpen !== undefined ? externalIsOpen : internalIsOpen;

  // Form data（FieldValue型を使用）
  const [formData, setFormData] = useState<Record<string, FieldValue>>({});

  // RecordType state（RecordTypeフィールドの選択値を管理）
  const [recordTypeFields, setRecordTypeFields] = useState<Record<string, string>>({});

  // Calendar-specific state
  const [activeTab, setActiveTab] = useState<ActivityType>(
    module === 'Calendar' ? 'Calendar' : 'Events'
  );
  const [calendarFormData, setCalendarFormData] = useState<Record<string, FieldValue>>({});
  const [eventsFormData, setEventsFormData] = useState<Record<string, FieldValue>>({});

  // Validation errors
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  // Success message
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  // Initialization tracking
  const isInitializedRef = useRef(false);
  const prevIsOpenRef = useRef(false);
  const successTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // ========================================
  // Hooks for record data (edit mode)
  // ========================================
  const {
    data: recordData,
    loading: recordDataLoading,
    error: recordDataError
  } = useRecordData({
    module: isCalendarVariant ? (module === 'Calendar' ? 'Calendar' : 'Events') : module,
    recordId: recordId,
    skip: !isEditMode || !isOpen
  });

  // ========================================
  // Hooks for default variant
  // ========================================
  const {
    fields: defaultFields,
    loading: defaultFieldsLoading,
    error: defaultFieldsError,
    editViewUrl: defaultEditViewUrl,
    moduleLabel: defaultModuleLabel,
    picklistDependency
  } = useQuickCreateFields(isCalendarVariant ? '' : module, recordTypeFields);

  const {
    save: defaultSave,
    isSaving: defaultIsSaving,
    error: defaultSaveError,
    clearError: clearDefaultSaveError
  } = useQuickCreateSave(isCalendarVariant ? '' : module);

  const { getFilteredFields, getFieldsToClear, hasDependency } = usePicklistDependency(picklistDependency);

  // ========================================
  // Hooks for calendar variant
  // ========================================
  const {
    calendarFields,
    eventsFields,
    currentFields: calendarCurrentFields,
    loading: calendarFieldsLoading,
    error: calendarFieldsError,
    editViewUrl: calendarEditViewUrl,
    availableUsers,
    timeOptions,
    parseDateTimeValue,
    combineDateTimeValue,
    parseReminderValue,
    combineReminderValue,
    transformInitialDataForEdit
  } = useCalendarFields({
    activeTab,
    initialData,
    recordId,
    recordTypeFields
  });

  const {
    save: calendarSave,
    isSaving: calendarIsSaving,
    error: calendarSaveError,
    clearError: clearCalendarSaveError
  } = useQuickCreateSave(isCalendarVariant ? activeTab : '');

  // ========================================
  // Computed values based on variant
  // ========================================
  const fields = isCalendarVariant ? calendarCurrentFields : defaultFields;
  // Include recordDataLoading in overall loading state for edit mode
  const fieldsLoading = (isCalendarVariant ? calendarFieldsLoading : defaultFieldsLoading) ||
    (isEditMode && recordDataLoading);
  const fieldsError = isCalendarVariant ? calendarFieldsError : defaultFieldsError || recordDataError;
  const moduleLabel = isCalendarVariant
    ? (activeTab === 'Calendar' ? t('LBL_TASK') : t('LBL_EVENT'))
    : defaultModuleLabel;
  const isSaving = isCalendarVariant ? calendarIsSaving : defaultIsSaving;
  const saveError = isCalendarVariant ? calendarSaveError : defaultSaveError;
  const clearSaveError = isCalendarVariant ? clearCalendarSaveError : clearDefaultSaveError;

  // Current form data for calendar variant
  const currentCalendarFormData = activeTab === 'Calendar' ? calendarFormData : eventsFormData;
  const setCurrentCalendarFormData = activeTab === 'Calendar' ? setCalendarFormData : setEventsFormData;

  /**
   * Apply picklist dependency filtering (default variant only)
   */
  const filteredFields = useMemo(() => {
    if (isCalendarVariant || !hasDependency) {
      return fields;
    }
    return getFilteredFields(fields, formData);
  }, [isCalendarVariant, fields, formData, hasDependency, getFilteredFields]);

  // ========================================
  // Effects
  // ========================================

  /**
   * Reset initialization when modal closes
   */
  useEffect(() => {
    if (prevIsOpenRef.current && !isOpen) {
      isInitializedRef.current = false;
      if (successTimeoutRef.current) {
        clearTimeout(successTimeoutRef.current);
        successTimeoutRef.current = null;
      }
    }
    prevIsOpenRef.current = isOpen;
  }, [isOpen]);

  /**
   * Cleanup on unmount
   */
  useEffect(() => {
    return () => {
      if (successTimeoutRef.current) {
        clearTimeout(successTimeoutRef.current);
      }
    };
  }, []);

  /**
   * Initialize form data (default variant)
   */
  useEffect(() => {
    // For edit mode, wait for recordData to be loaded
    if (!isCalendarVariant && isOpen && defaultFields.length > 0 && !isInitializedRef.current) {
      // For edit mode, wait for recordData (skip if still loading)
      if (isEditMode && recordDataLoading) {
        return;
      }

      // Merge initialData and recordData (recordData takes priority for edit mode)
      const initial: Record<string, any> = {
        ...initialData,
        ...(isEditMode && recordData ? recordData : {})
      };

      // Add record parameter for edit mode
      if (isEditMode && recordId) {
        initial.record = recordId;
      }

      defaultFields.forEach(field => {
        if (!(field.name in initial)) {
          const defaultValue = field.fieldinfo?.defaultvalue;
          if (defaultValue !== undefined && defaultValue !== null && defaultValue !== '') {
            initial[field.name] = defaultValue;
          }
          // 新規作成モードの場合、担当フィールドにログインユーザーをデフォルト設定
          else if (!isEditMode && field.name === 'assigned_user_id') {
            initial[field.name] = (window as any).current_user_id || '1';
          }
        }
      });

      setFormData(initial);
      setValidationErrors({});
      setSuccessMessage(null);
      clearSaveError();
      isInitializedRef.current = true;
    }
  }, [isCalendarVariant, isOpen, defaultFields, initialData, isEditMode, recordId, recordData, recordDataLoading, clearSaveError]);

  /**
   * Initialize form data (calendar variant - edit mode)
   * Uses recordData from useRecordData hook (fetched via GetRecord API)
   */
  useEffect(() => {
    if (!isCalendarVariant || !isOpen || !isEditMode || isInitializedRef.current) {
      return;
    }

    // Wait for recordData to be loaded
    if (recordDataLoading) {
      return;
    }

    // If we have a recordId, we MUST wait for recordData from API
    // Do NOT fall back to initialData when recordId exists but recordData is null
    // (this can happen during the async fetch)
    if (recordId && !recordData) {
      return;
    }

    // Use recordData from API if available, fall back to initialData only when no recordId
    const dataSource = recordData || initialData;
    if (!dataSource || Object.keys(dataSource).length === 0) {
      return;
    }

    // transformInitialDataForEdit already handles datetime conversion
    // but recordData from useRecordData is already transformed, so we need to handle both cases
    const transformedData = recordData
      ? { ...recordData, record: recordId } // recordData is already transformed
      : transformInitialDataForEdit(initialData, activeTab);

    // Determine tab based on activitytype
    const activityType = dataSource.activitytype;
    const isTask = activityType === 'Task';
    const targetTab: ActivityType = isTask ? 'Calendar' : 'Events';

    if (activeTab !== targetTab) {
      setActiveTab(targetTab);
    }

    if (targetTab === 'Calendar') {
      setCalendarFormData(transformedData);
    } else {
      setEventsFormData(transformedData);
    }

    isInitializedRef.current = true;
  }, [isCalendarVariant, isOpen, isEditMode, initialData, recordData, recordDataLoading, recordId, activeTab, transformInitialDataForEdit]);

  /**
   * Initialize form data (calendar variant - new mode)
   */
  useEffect(() => {
    if (!isCalendarVariant || isEditMode || !isOpen || isInitializedRef.current) {
      return;
    }

    const getFieldInitialValue = (field: FieldInfo): any => {
      const defaultValue = field.fieldinfo?.defaultvalue;
      if (defaultValue !== undefined && defaultValue !== null && defaultValue !== '' && defaultValue !== false) {
        return defaultValue;
      }
      if (field.name === 'assigned_user_id') {
        return (window as any).current_user_id || '1';
      }
      if (field.mandatory && field.picklistValues && field.picklistValues.length > 0) {
        return field.picklistValues[0].value;
      }
      return undefined;
    };

    if (calendarFields.length > 0) {
      const calInitial: Record<string, any> = { ...initialData };
      calendarFields.forEach(field => {
        if (!(field.name in calInitial)) {
          const initialValue = getFieldInitialValue(field);
          if (initialValue !== undefined) {
            calInitial[field.name] = initialValue;
          }
        }
      });
      setCalendarFormData(calInitial);
    }

    if (eventsFields.length > 0) {
      const evtInitial: Record<string, any> = { ...initialData };
      eventsFields.forEach(field => {
        if (!(field.name in evtInitial)) {
          const initialValue = getFieldInitialValue(field);
          if (initialValue !== undefined) {
            evtInitial[field.name] = initialValue;
          }
        }
      });
      setEventsFormData(evtInitial);
    }

    if (calendarFields.length > 0 && eventsFields.length > 0) {
      // Set initial RecordType from activitytype value (uses same logic as getFieldInitialValue)
      // This ensures RecordType filtering is applied on initial load
      const activityTypeField = eventsFields.find(f => f.name === 'activitytype');
      if (activityTypeField) {
        // Use getFieldInitialValue logic to get the actual initial value
        const initialActivityType = getFieldInitialValue(activityTypeField);
        if (initialActivityType && typeof initialActivityType === 'string') {
          setRecordTypeFields(prev => ({
            ...prev,
            activitytype: initialActivityType
          }));
        }
      }

      setValidationErrors({});
      setSuccessMessage(null);
      clearSaveError();
      isInitializedRef.current = true;
    }
  }, [isCalendarVariant, isEditMode, isOpen, calendarFields, eventsFields, initialData, clearSaveError]);

  // ========================================
  // Handlers
  // ========================================

  /**
   * Handle modal open/close
   */
  const handleOpenChange = useCallback((open: boolean) => {
    if (externalIsOpen === undefined) {
      setInternalIsOpen(open);
    }

    if (isCalendarVariant) {
      // WebComponent compatibility: emit { isOpen: boolean }
      onOpenChange?.({ isOpen: open } as any);
    } else {
      onOpenChange?.(open);
    }

    if (!open) {
      onCancel?.();
    }
  }, [externalIsOpen, isCalendarVariant, onOpenChange, onCancel]);

  /**
   * Handle RecordType field change
   * RecordTypeフィールドの値が変更された時、フィールド一覧を再取得する
   */
  const handleRecordTypeChange = useCallback((fieldName: string, value: string) => {
    // RecordType選択値を更新（これによりuseQuickCreateFieldsが再取得を行う）
    setRecordTypeFields(prev => ({
      ...prev,
      [fieldName]: value
    }));

    // フォームデータも更新
    setFormData(prev => ({
      ...prev,
      [fieldName]: value
    }));

    // バリデーションエラーをクリア
    setValidationErrors({});
    setSuccessMessage(null);
    clearSaveError();
  }, [clearSaveError]);

  /**
   * Handle field change (default variant)
   */
  const handleDefaultFieldChange = useCallback((fieldName: string, value: any) => {
    setFormData(prev => {
      const updated = {
        ...prev,
        [fieldName]: value
      };

      if (hasDependency) {
        const fieldsToClear = getFieldsToClear(fieldName, value, prev);
        for (const targetField of fieldsToClear) {
          updated[targetField] = '';
        }
      }

      return updated;
    });

    if (validationErrors[fieldName]) {
      setValidationErrors(prev => {
        const next = { ...prev };
        delete next[fieldName];
        return next;
      });
    }

    setSuccessMessage(null);
    clearSaveError();
  }, [hasDependency, getFieldsToClear, validationErrors, clearSaveError]);

  /**
   * Handle field change (calendar variant)
   */
  const handleCalendarFieldChange = useCallback((fieldName: string, value: any) => {
    setCurrentCalendarFormData(prev => ({
      ...prev,
      [fieldName]: value
    }));

    if (validationErrors[fieldName]) {
      setValidationErrors(prev => {
        const next = { ...prev };
        delete next[fieldName];
        return next;
      });
    }

    setSuccessMessage(null);
    clearSaveError();
  }, [setCurrentCalendarFormData, validationErrors, clearSaveError]);

  /**
   * Handle tab change (calendar variant)
   */
  const handleTabChange = useCallback((tab: ActivityType) => {
    setActiveTab(tab);
    setValidationErrors({});
    setSuccessMessage(null);
    clearSaveError();
  }, [clearSaveError]);

  /**
   * Validate form
   */
  const validateForm = useCallback((): boolean => {
    const errors: Record<string, string> = {};
    const targetFields = isCalendarVariant ? calendarCurrentFields : defaultFields;
    const targetFormData = isCalendarVariant ? currentCalendarFormData : formData;

    targetFields.forEach(field => {
      const value = targetFormData[field.name];

      // Required check
      if (field.mandatory) {
        const isEmpty = value === undefined || value === null || value === '' || value === false;
        if (isEmpty) {
          errors[field.name] = t('LBL_FIELD_REQUIRED', field.label);
          return;
        }
      }

      // Max length check
      if (field.maxlength) {
        if (typeof value === 'string' && value.length > field.maxlength) {
          errors[field.name] = t('LBL_FIELD_MAX_LENGTH', field.label, field.maxlength);
          return;
        }
      }

    });

    // Date range validation (calendar variant)
    if (isCalendarVariant) {
      const dateStart = currentCalendarFormData['date_start'];
      const dueDate = currentCalendarFormData['due_date'];
      if (typeof dateStart === 'string' && typeof dueDate === 'string' && dateStart && dueDate) {
        if (new Date(dateStart) > new Date(dueDate)) {
          errors['due_date'] = t('LBL_END_DATE_AFTER_START');
        }
      }
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  }, [isCalendarVariant, calendarCurrentFields, defaultFields, currentCalendarFormData, formData]);

  /**
   * Handle save
   */
  const handleSave = useCallback(async () => {
    if (!validateForm()) {
      return;
    }

    const saveFn = isCalendarVariant ? calendarSave : defaultSave;
    const saveData = isCalendarVariant ? currentCalendarFormData : formData;

    const result = await saveFn(saveData);

    if (result.success) {
      const messageKey = isEditMode ? 'LBL_UPDATED_SUCCESS' : 'LBL_CREATED_SUCCESS';
      const baseMessage = t(messageKey, moduleLabel || module);
      setSuccessMessage(
        baseMessage + (result.recordLabel ? ` - ${result.recordLabel}` : '')
      );

      onSave?.(result);

      if (successTimeoutRef.current) {
        clearTimeout(successTimeoutRef.current);
      }

      successTimeoutRef.current = setTimeout(() => {
        handleOpenChange(false);
      }, 1500);
    }
  }, [
    validateForm, isCalendarVariant, calendarSave, defaultSave,
    currentCalendarFormData, formData, isEditMode, moduleLabel, module,
    onSave, handleOpenChange
  ]);

  /**
   * Handle go to full form
   * 旧版Vtiger.js quickCreateGoToFullFormと同様にPOSTでフォーム送信する
   */
  const handleGoToFullForm = useCallback(() => {
    let editViewUrl: string | null = null;
    let targetFormData: Record<string, unknown> = {};

    if (isCalendarVariant) {
      if (isEditMode && recordId) {
        editViewUrl = `index.php?module=${activeTab}&view=Edit&record=${recordId}`;
      } else if (calendarEditViewUrl) {
        editViewUrl = calendarEditViewUrl;
        // Calendar/Events Edit.phpではmodeパラメータでEvents関数を呼び出すため追加
        // これによりcontactidlistが正しく処理される
        if (activeTab === 'Events') {
          editViewUrl += '&mode=Events';
        }
      }
      // currentCalendarFormDataはuseCallbackの依存関係で正しく追跡されないため、
      // activeTabに基づいて直接状態を参照する
      targetFormData = activeTab === 'Calendar' ? calendarFormData : eventsFormData;
    } else {
      if (defaultEditViewUrl) {
        editViewUrl = defaultEditViewUrl;
      }
      targetFormData = formData;
    }

    if (!editViewUrl) return;

    if (onGoToFullForm) {
      onGoToFullForm({ editUrl: editViewUrl, formData: targetFormData });
      return;
    }

    // 旧版Vtiger.js quickCreateGoToFullFormと同様にPOSTでフォーム送信
    // これによりcontactidlist等のデータが正しくEditViewに渡される
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = editViewUrl;
    form.style.display = 'none';

    // フォームデータをhidden inputとして追加
    Object.entries(targetFormData).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;

      const input = document.createElement('input');
      input.type = 'hidden';

      // contact_idの処理（旧版と同じ形式で送信）
      // - 単一値の場合: contact_id=54 として送信（Edit.php 76-83行目で処理）
      // - 複数値の場合: contactidlist=54;53 として送信（Edit.php 183-189行目で処理）
      // MultireferenceFieldは "54;53" のような文字列形式で値を返すため、
      // 配列形式と文字列形式の両方を処理する
      if (key === 'contact_id') {
        const strValue = String(value);
        const ids = strValue.includes(';') ? strValue.split(';') : [strValue];

        if (ids.length > 1) {
          // 複数コンタクトの場合はcontactidlistを使用
          input.name = 'contactidlist';
          input.value = ids.join(';');
        } else {
          // 単一コンタクトの場合はcontact_idを使用
          input.name = 'contact_id';
          input.value = ids[0];
        }
      } else if (Array.isArray(value)) {
        // 複数選択肢の場合は |##| 区切りで渡す（旧版の仕様に準拠）
        input.name = key;
        input.value = value.join(' |##| ');
      } else {
        input.name = key;
        input.value = String(value);
      }

      form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
  }, [
    isCalendarVariant, isEditMode, recordId, activeTab,
    calendarEditViewUrl, calendarFormData, eventsFormData,
    defaultEditViewUrl, formData, onGoToFullForm
  ]);

  // ========================================
  // Render
  // ========================================
  const errorMessage = fieldsError || saveError;

  return (
    <Dialog open={isOpen} onOpenChange={handleOpenChange}>
      <DialogContent
        className={cn(
          'max-h-[90vh] overflow-hidden flex flex-col p-0',
          'sm:max-w-[900px]'
        )}
        closeButtonClassName="absolute top-2 right-3 !text-white hover:!text-gray-200 transition-opacity"
      >
        {/* Header - 両バリアントで統一コンポーネントを使用 */}
        <QuickCreateHeader
          moduleLabel={moduleLabel || module}
          variant={variant}
          isEditMode={isEditMode}
          activeTab={activeTab}
        />

        {/* Content */}
        <div className="flex-1 overflow-y-auto px-8 py-4">
          {/* Error message - アクセシビリティ対応 */}
          {errorMessage && (
            <Alert
              variant="destructive"
              className="mb-4"
              role="alert"
              aria-live="assertive"
              aria-atomic="true"
            >
              <XCircle className="h-4 w-4" aria-hidden="true" />
              <AlertDescription>{errorMessage}</AlertDescription>
            </Alert>
          )}

          {/* Success message - アクセシビリティ対応 */}
          {successMessage && (
            <Alert
              className="mb-4 bg-green-50 border-green-200 text-green-800"
              role="status"
              aria-live="polite"
              aria-atomic="true"
            >
              <CheckCircle className="h-4 w-4" aria-hidden="true" />
              <AlertDescription>{successMessage}</AlertDescription>
            </Alert>
          )}

          {/* Loading - アクセシビリティ対応 */}
          {fieldsLoading ? (
            <div
              className="flex items-center justify-center py-12"
              role="status"
              aria-live="polite"
              aria-busy="true"
            >
              <Loader2 className="h-8 w-8 animate-spin text-gray-400" aria-hidden="true" />
              <span className="ml-2 text-gray-500">{t('LBL_LOADING_FIELDS')}</span>
            </div>
          ) : isCalendarVariant ? (
            /* Calendar form */
            <CalendarForm
              activeTab={activeTab}
              onTabChange={handleTabChange}
              formData={currentCalendarFormData}
              onFieldChange={handleCalendarFieldChange}
              onRecordTypeChange={handleRecordTypeChange}
              currentFields={calendarCurrentFields}
              isSaving={isSaving}
              successMessage={successMessage}
              validationErrors={validationErrors}
              isEditMode={isEditMode}
              availableUsers={availableUsers}
              timeOptions={timeOptions}
              parseDateTimeValue={parseDateTimeValue}
              combineDateTimeValue={combineDateTimeValue}
              parseReminderValue={parseReminderValue}
              combineReminderValue={combineReminderValue}
              initialSelectedInvitees={
                isEditMode && recordData?.selectedusers
                  ? (recordData.selectedusers as string[])
                  : undefined
              }
            />
          ) : (
            /* Default form */
            <QuickCreateForm
              module={module}
              fields={filteredFields}
              formData={formData}
              onFieldChange={handleDefaultFieldChange}
              onRecordTypeChange={handleRecordTypeChange}
              isSaving={isSaving}
              disabled={!!successMessage}
              errors={validationErrors}
            />
          )}
        </div>

        {/* Footer */}
        {!fieldsLoading && (
          <QuickCreateFooter
            module={module}
            onSave={handleSave}
            onCancel={() => handleOpenChange(false)}
            onGoToFullForm={handleGoToFullForm}
            isSaving={isSaving}
            saveDisabled={!!successMessage || fields.length === 0}
            isEditMode={isEditMode}
          />
        )}
      </DialogContent>
    </Dialog>
  );
};

export default QuickCreate;
