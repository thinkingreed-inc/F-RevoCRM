import React, { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import { createPortal } from 'react-dom';
import { Calendar, CheckSquare, X } from 'lucide-react';
import { FieldRenderer } from '../FieldRenderer';
import { Input } from '../ui/input';
import { Checkbox } from '../ui/checkbox';
import { FieldInfo, FieldValue } from '../../types/field';
import { cn } from '../../lib/utils';
import { useTranslation } from '../../hooks/useTranslation';

/**
 * Activity type: Calendar (ToDo) or Events
 */
type ActivityType = 'Calendar' | 'Events';

/**
 * User information for invitee selection
 */
interface UserInfo {
  id: string;
  name: string;
}

/**
 * Time option for 10-minute interval selection
 */
interface TimeOption {
  value: string;
  label: string;
}

/**
 * CalendarForm props
 */
export interface CalendarFormProps {
  /** Active tab (Calendar=ToDo, Events=イベント) */
  activeTab: ActivityType;
  /** Callback when tab changes */
  onTabChange: (tab: ActivityType) => void;
  /** Current form data */
  formData: Record<string, FieldValue>;
  /** Callback when field value changes */
  onFieldChange: (fieldName: string, value: FieldValue) => void;
  /** Fields for current tab */
  currentFields: FieldInfo[];
  /** Whether form is being saved */
  isSaving: boolean;
  /** Whether save was successful (show success message) */
  successMessage: string | null;
  /** Validation errors */
  validationErrors: Record<string, string>;
  /** Whether in edit mode */
  isEditMode?: boolean;
  /** Available users for invitee selection (Events only) */
  availableUsers: UserInfo[];
  /** Time options (10-minute intervals) */
  timeOptions: TimeOption[];
  /** Function to parse datetime value */
  parseDateTimeValue: (value: string | undefined) => { date: string; time: string };
  /** Function to combine datetime value */
  combineDateTimeValue: (date: string, time: string) => string;
  /** Function to parse reminder value */
  parseReminderValue: (value: FieldValue) => { days: number; hours: number; minutes: number };
  /** Function to combine reminder value */
  combineReminderValue: (days: number, hours: number, minutes: number) => number;
  /** Initial selected invitees (user IDs) for edit mode */
  initialSelectedInvitees?: string[];
  /** Callback when RecordType field changes */
  onRecordTypeChange?: (fieldName: string, value: string) => void;
}

/**
 * CalendarForm - Calendar/Events specific form component
 *
 * Handles:
 * - Tab switching (ToDo/Event)
 * - Custom datetime inputs (date + 10-minute time select)
 * - All-day checkbox
 * - Reminder settings
 * - Invitee selection (Events only)
 *
 * @example
 * ```tsx
 * <CalendarForm
 *   activeTab="Events"
 *   onTabChange={setActiveTab}
 *   formData={formData}
 *   onFieldChange={handleFieldChange}
 *   currentFields={fields}
 *   isSaving={false}
 *   successMessage={null}
 *   validationErrors={{}}
 *   availableUsers={users}
 *   timeOptions={timeOptions}
 *   parseDateTimeValue={parseDateTimeValue}
 *   combineDateTimeValue={combineDateTimeValue}
 *   parseReminderValue={parseReminderValue}
 *   combineReminderValue={combineReminderValue}
 * />
 * ```
 */
export const CalendarForm: React.FC<CalendarFormProps> = ({
  activeTab,
  onTabChange,
  formData,
  onFieldChange,
  currentFields,
  isSaving,
  successMessage,
  validationErrors,
  isEditMode = false,
  availableUsers,
  timeOptions,
  parseDateTimeValue,
  combineDateTimeValue,
  parseReminderValue,
  combineReminderValue,
  initialSelectedInvitees,
  onRecordTypeChange,
}) => {
  const { t } = useTranslation();

  // Invitee selection state
  const [selectedInvitees, setSelectedInvitees] = useState<string[]>([]);
  const [inviteeSearchTerm, setInviteeSearchTerm] = useState<string>('');
  const [isInviteeDropdownOpen, setIsInviteeDropdownOpen] = useState<boolean>(false);
  const inviteeDropdownRef = useRef<HTMLDivElement>(null);
  const inviteeInputContainerRef = useRef<HTMLDivElement>(null);
  const [inviteeDropdownPosition, setInviteeDropdownPosition] = useState<{ top: number; left: number; width: number } | null>(null);
  const initialInviteesLoadedRef = useRef<boolean>(false);

  const isDisabled = isSaving || !!successMessage;

  /**
   * All-day checkbox state
   */
  const isAllDay = formData['is_allday'] === true || formData['is_allday'] === '1' || formData['is_allday'] === 'true';

  /**
   * Reminder state
   */
  const reminderValue = formData['reminder_time'];
  const reminderParsed = parseReminderValue(reminderValue);
  const isReminderEnabled = reminderValue && reminderValue !== '' && reminderValue !== '0' && reminderValue !== 0;

  /**
   * Filtered users for dropdown (excluding already selected)
   */
  const filteredUsers = useMemo(() => {
    if (!inviteeSearchTerm) return availableUsers;
    const lowerSearch = inviteeSearchTerm.toLowerCase();
    return availableUsers.filter(user =>
      user.name.toLowerCase().includes(lowerSearch)
    );
  }, [availableUsers, inviteeSearchTerm]);

  /**
   * Update invitee dropdown position
   */
  const updateInviteeDropdownPosition = useCallback(() => {
    if (inviteeInputContainerRef.current) {
      const rect = inviteeInputContainerRef.current.getBoundingClientRect();
      setInviteeDropdownPosition({
        top: rect.bottom + window.scrollY,
        left: rect.left + window.scrollX,
        width: rect.width
      });
    }
  }, []);

  /**
   * Close invitee dropdown when clicking outside
   */
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Node;
      const isInsideDropdown = inviteeDropdownRef.current?.contains(target);
      const isInsideInput = inviteeInputContainerRef.current?.contains(target);

      if (!isInsideDropdown && !isInsideInput) {
        setIsInviteeDropdownOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  /**
   * Update invitee dropdown position on scroll/resize
   */
  useEffect(() => {
    if (!isInviteeDropdownOpen) return;

    const handleScrollOrResize = () => {
      updateInviteeDropdownPosition();
    };

    window.addEventListener('scroll', handleScrollOrResize, true);
    window.addEventListener('resize', handleScrollOrResize);

    return () => {
      window.removeEventListener('scroll', handleScrollOrResize, true);
      window.removeEventListener('resize', handleScrollOrResize);
    };
  }, [isInviteeDropdownOpen, updateInviteeDropdownPosition]);

  /**
   * Initialize selectedInvitees from initialSelectedInvitees (edit mode)
   * This should only run once when initialSelectedInvitees is first provided
   */
  useEffect(() => {
    if (initialSelectedInvitees && initialSelectedInvitees.length > 0 && !initialInviteesLoadedRef.current) {
      setSelectedInvitees(initialSelectedInvitees);
      initialInviteesLoadedRef.current = true;
    }
  }, [initialSelectedInvitees]);

  /**
   * Reset invitee state when tab changes (only for new mode)
   * In edit mode, preserve the selected invitees
   */
  useEffect(() => {
    if (!isEditMode) {
      setSelectedInvitees([]);
      setInviteeSearchTerm('');
      setIsInviteeDropdownOpen(false);
      initialInviteesLoadedRef.current = false;
    }
  }, [activeTab, isEditMode]);

  /**
   * Update formData with selected invitees
   */
  useEffect(() => {
    if (activeTab === 'Events') {
      onFieldChange('selectedusers', selectedInvitees);
    }
  }, [selectedInvitees, activeTab, onFieldChange]);

  /**
   * Categorize fields into subject, datetime, and others grouped by block
   */
  const categorizeFields = (fields: FieldInfo[]) => {
    const subject = fields.find(f => f.name === 'subject');
    const dateStart = fields.find(f => f.name === 'date_start');
    const dueDate = fields.find(f => f.name === 'due_date');
    const reminderTime = fields.find(f => f.name === 'reminder_time');

    // Exclude special fields, group remaining by block
    const specialFields = ['subject', 'date_start', 'due_date', 'reminder_time'];
    const otherFields = fields.filter(f => !specialFields.includes(f.name));

    // Group by block
    const othersByBlock: Record<string, FieldInfo[]> = {};
    otherFields.forEach(field => {
      const blockLabel = field.block || t('LBL_BASIC_INFORMATION');
      if (!othersByBlock[blockLabel]) {
        othersByBlock[blockLabel] = [];
      }
      othersByBlock[blockLabel].push(field);
    });

    return { subject, dateStart, dueDate, reminderTime, othersByBlock };
  };

  const { subject, dateStart, dueDate, reminderTime, othersByBlock } = categorizeFields(currentFields);

  /**
   * Handle tab change
   */
  const handleTabChange = useCallback((tab: ActivityType) => {
    if (!isEditMode) {
      onTabChange(tab);
    }
  }, [isEditMode, onTabChange]);

  /**
   * Handle all-day checkbox change
   */
  const handleAllDayChange = useCallback((checked: boolean) => {
    onFieldChange('is_allday', checked);
  }, [onFieldChange]);

  /**
   * Handle reminder toggle
   */
  const handleReminderToggle = useCallback((enabled: boolean) => {
    if (enabled) {
      // Default: 15 minutes before
      onFieldChange('reminder_time', 15);
    } else {
      onFieldChange('reminder_time', '');
    }
  }, [onFieldChange]);

  /**
   * Handle reminder value change
   */
  const handleReminderChange = useCallback((field: 'days' | 'hours' | 'minutes', value: number) => {
    const current = parseReminderValue(formData['reminder_time']);
    const updated = { ...current, [field]: value };
    const newValue = combineReminderValue(updated.days, updated.hours, updated.minutes);
    onFieldChange('reminder_time', newValue > 0 ? newValue : '');
  }, [formData, parseReminderValue, combineReminderValue, onFieldChange]);

  /**
   * Handle invitee add
   */
  const handleAddInvitee = useCallback((userId: string) => {
    if (!selectedInvitees.includes(userId)) {
      setSelectedInvitees(prev => [...prev, userId]);
    }
    setInviteeSearchTerm('');
    setIsInviteeDropdownOpen(false);
  }, [selectedInvitees]);

  /**
   * Handle invitee remove
   */
  const handleRemoveInvitee = useCallback((userId: string) => {
    setSelectedInvitees(prev => prev.filter(id => id !== userId));
  }, []);

  /**
   * Render datetime field with date input + time select (ラベルと入力項目を横並び)
   * FieldRendererと同じレイアウト: ラベル右寄せ固定幅 + 入力欄
   */
  const renderDateTimeField = (
    field: FieldInfo | undefined,
    fieldName: string,
    label: string
  ) => {
    if (!field) return null;

    const rawValue = formData[fieldName];
    const currentValue = typeof rawValue === 'string' ? rawValue : undefined;
    const { date, time } = parseDateTimeValue(currentValue);
    const error = validationErrors[fieldName];

    return (
      <div className="flex items-start gap-2">
        {/* ラベル - FieldRendererと同じスタイル */}
        <label
          className={cn(
            'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]',
            isDisabled && 'text-gray-400'
          )}
        >
          {label}
        </label>
        {/* 必須マーク：固定幅で位置を確保し、入力欄の開始位置を揃える */}
        <span className="w-3 leading-[30px] text-red-500 text-center flex-shrink-0" aria-hidden="true">
          {field.mandatory ? '*' : ''}
        </span>
        {/* 入力欄 */}
        <div className="flex-1 min-w-0">
          <div className="flex gap-2">
            {/* Date input */}
            <Input
              type="date"
              value={date}
              onChange={(e) => {
                const newValue = combineDateTimeValue(e.target.value, isAllDay ? '' : time);
                onFieldChange(fieldName, newValue);
              }}
              disabled={isDisabled}
              className={cn(isAllDay ? 'w-full' : 'flex-1', error && 'border-red-500')}
            />
            {/* Time select (hidden when all-day) */}
            {!isAllDay && (
              <select
                value={time}
                onChange={(e) => {
                  const newValue = combineDateTimeValue(date, e.target.value);
                  onFieldChange(fieldName, newValue);
                }}
                disabled={isDisabled}
                className={cn(
                  'w-28 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
                  'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                  'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                  error && 'border-red-500 bg-red-50'
                )}
              >
                <option value="">--:--</option>
                {timeOptions.map(opt => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            )}
          </div>
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

  return (
    <div className="space-y-4">
      {/* Tab switcher */}
      <div className="flex border-b border-gray-300">
        <button
          type="button"
          onClick={() => handleTabChange('Calendar')}
          disabled={isEditMode}
          className={cn(
            'flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition-colors',
            activeTab === 'Calendar'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            isEditMode && 'cursor-not-allowed opacity-60'
          )}
        >
          <CheckSquare className="h-4 w-4" />
          {t('LBL_TASK')}
        </button>
        <button
          type="button"
          onClick={() => handleTabChange('Events')}
          disabled={isEditMode}
          className={cn(
            'flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition-colors',
            activeTab === 'Events'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            isEditMode && 'cursor-not-allowed opacity-60'
          )}
        >
          <Calendar className="h-4 w-4" />
          {t('LBL_EVENT')}
        </button>
      </div>

      {/* 特殊フィールドセクション（タイトル・日時） */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 pr-8 pb-2">
        {/* Subject field - 左側列 */}
        {subject && (
          <div>
            <FieldRenderer
              field={subject}
              value={formData[subject.name]}
              onChange={onFieldChange}
              onRecordTypeChange={onRecordTypeChange}
              disabled={isDisabled}
              error={validationErrors[subject.name]}
              formData={formData}
            />
          </div>
        )}
        {/* 右側列は空 */}
        {subject && <div />}

        {/* 開始日時 - 左側列 */}
        <div>
          {renderDateTimeField(dateStart, 'date_start', dateStart?.label || '開始日時')}
        </div>
        {/* 終了日時 - 右側列 */}
        <div>
          {renderDateTimeField(dueDate, 'due_date', dueDate?.label || '終了日時')}
        </div>

        {/* All-day checkbox (Events only) - 左側列、ラベル | 入力の横並びレイアウト */}
        {activeTab === 'Events' && (
          <div className="flex items-start gap-2">
            <label
              className={cn(
                'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-[30px]',
                isDisabled && 'text-gray-400'
              )}
            >
              {t('LBL_ALL_DAY')}
            </label>
            {/* 必須マーク用スペース（終日は必須ではないが、位置を揃えるため） */}
            <span className="w-3 leading-[30px] flex-shrink-0" aria-hidden="true" />
            <div className="flex-1 min-w-0 flex items-center h-[30px]">
              <Checkbox
                checked={isAllDay}
                onCheckedChange={(checked) => handleAllDayChange(checked === true)}
                disabled={isDisabled}
              />
            </div>
          </div>
        )}
      </div>

      {/* Other fields - ブロック別にグループ化（QuickCreateFormと同じスタイル） */}
      <div className="space-y-3">
        {Object.entries(othersByBlock).map(([blockName, blockFields]) => {
          // 「アラーム情報」ブロックの場合は、リマインダー設定もここに含める
          const isAlarmBlock = reminderTime && blockName === reminderTime.block;

          return (
            <div key={blockName} className="quickcreate-block">
              <h4 className="fieldBlockHeader font-bold leading-[1.1] mt-0 mb-2 pb-1 border-b border-gray-300">
                {blockName}
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 pr-8">
                {blockFields.map(field => (
                  <div
                    key={field.name}
                    className={cn(
                      'quickcreate-field',
                      // TextAreaは2カラム幅
                      (field.uitype === '19' || field.uitype === '21') && 'md:col-span-2'
                    )}
                  >
                    <FieldRenderer
                      field={field}
                      value={formData[field.name]}
                      onChange={onFieldChange}
                      onRecordTypeChange={onRecordTypeChange}
                      disabled={isDisabled}
                      error={validationErrors[field.name]}
                      formData={formData}
                    />
                  </div>
                ))}
                {/* リマインダー設定をアラーム情報ブロック内に表示 */}
                {isAlarmBlock && (
                  <div className="md:col-span-2">
                    <div className="flex items-start gap-2">
                      <label
                        className={cn(
                          'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-tight min-h-[54px] pt-[18px] flex items-start justify-end',
                          isDisabled && 'text-gray-400'
                        )}
                      >
                        {t('LBL_SEND_NOTIFICATION')}
                      </label>
                      <span className="w-3 min-h-[54px] pt-[18px] flex-shrink-0" aria-hidden="true" />
                      <div className={cn(
                        "flex-1 min-w-0 pt-3 px-3 bg-gray-50 border border-gray-200 rounded-md",
                        !isReminderEnabled && "pb-3"
                      )}>
                        <div className="flex items-center gap-2 h-[30px] mb-0">
                          <Checkbox
                            id="reminder_enabled"
                            checked={!!isReminderEnabled}
                            onCheckedChange={(checked) => handleReminderToggle(checked === true)}
                            disabled={isDisabled}
                          />
                          <label htmlFor="reminder_enabled" className={cn(
                            'text-md text-gray-700 cursor-pointer !mb-0',
                            isDisabled && 'text-gray-400'
                          )}>
                            {t('LBL_SET_REMINDER')}
                          </label>
                        </div>
                        {isReminderEnabled && (
                          <div className="flex flex-wrap items-center gap-2 text-md mt-3 pt-3 pb-3 border-t border-gray-200">
                            <span className="text-gray-700">{t('LBL_START')}</span>
                            <select
                              value={reminderParsed.days}
                              onChange={(e) => handleReminderChange('days', parseInt(e.target.value, 10))}
                              disabled={isDisabled}
                              className={cn(
                                'w-20 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
                                'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                                'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50'
                              )}
                            >
                              {Array.from({ length: 32 }, (_, i) => (
                                <option key={i} value={i}>{i}</option>
                              ))}
                            </select>
                            <span className="text-gray-700">{t('LBL_DAYS')}</span>
                            <select
                              value={reminderParsed.hours}
                              onChange={(e) => handleReminderChange('hours', parseInt(e.target.value, 10))}
                              disabled={isDisabled}
                              className={cn(
                                'w-20 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
                                'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                                'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50'
                              )}
                            >
                              {Array.from({ length: 24 }, (_, i) => (
                                <option key={i} value={i}>{i}</option>
                              ))}
                            </select>
                            <span className="text-gray-700">{t('LBL_HOURS')}</span>
                            <select
                              value={reminderParsed.minutes}
                              onChange={(e) => handleReminderChange('minutes', parseInt(e.target.value, 10))}
                              disabled={isDisabled}
                              className={cn(
                                'w-20 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
                                'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                                'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50'
                              )}
                            >
                              {Array.from({ length: 59 }, (_, i) => i + 1).map(m => (
                                <option key={m} value={m}>{m}</option>
                              ))}
                            </select>
                            <span className="text-gray-700">{t('LBL_MINUTES_BEFORE')}</span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          );
        })}

        {/* reminder_timeフィールドがあるが、そのブロックがothersByBlockに存在しない場合（独立ブロック） */}
        {reminderTime && !Object.keys(othersByBlock).includes(reminderTime.block || '') && (
          <div className="quickcreate-block">
            <h4 className="fieldBlockHeader font-bold leading-[1.1] mt-0 mb-2 pb-1 border-b border-gray-300">
              {reminderTime.block || t('LBL_ALARM_INFORMATION')}
            </h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 pr-8">
              <div className="md:col-span-2">
                <div className="flex items-start gap-2">
                  <label
                    className={cn(
                      'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-tight min-h-[54px] pt-[18px] flex items-start justify-end',
                      isDisabled && 'text-gray-400'
                    )}
                  >
                    {t('LBL_SEND_NOTIFICATION')}
                  </label>
                  <span className="w-3 min-h-[54px] pt-[18px] flex-shrink-0" aria-hidden="true" />
                  <div className={cn(
                    "flex-1 min-w-0 pt-3 px-3 bg-gray-50 border border-gray-200 rounded-md",
                    !isReminderEnabled && "pb-3"
                  )}>
                    <div className="flex items-center gap-2 h-[30px] mb-0">
                      <Checkbox
                        id="reminder_enabled_standalone"
                        checked={!!isReminderEnabled}
                        onCheckedChange={(checked) => handleReminderToggle(checked === true)}
                        disabled={isDisabled}
                      />
                      <label htmlFor="reminder_enabled_standalone" className={cn(
                        'text-md text-gray-700 cursor-pointer !mb-0',
                        isDisabled && 'text-gray-400'
                      )}>
                        {t('LBL_SET_REMINDER')}
                      </label>
                    </div>
                    {isReminderEnabled && (
                      <div className="flex flex-wrap items-center gap-2 text-md mt-3 pt-3 pb-3 border-t border-gray-200">
                        <span className="text-gray-700">{t('LBL_START')}</span>
                        <select
                          value={reminderParsed.days}
                          onChange={(e) => handleReminderChange('days', parseInt(e.target.value, 10))}
                          disabled={isDisabled}
                          className={cn(
                            'w-20 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
                            'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                            'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50'
                          )}
                        >
                          {Array.from({ length: 32 }, (_, i) => (
                            <option key={i} value={i}>{i}</option>
                          ))}
                        </select>
                        <span className="text-gray-700">{t('LBL_DAYS')}</span>
                        <select
                          value={reminderParsed.hours}
                          onChange={(e) => handleReminderChange('hours', parseInt(e.target.value, 10))}
                          disabled={isDisabled}
                          className={cn(
                            'w-20 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
                            'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                            'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50'
                          )}
                        >
                          {Array.from({ length: 24 }, (_, i) => (
                            <option key={i} value={i}>{i}</option>
                          ))}
                        </select>
                        <span className="text-gray-700">{t('LBL_HOURS')}</span>
                        <select
                          value={reminderParsed.minutes}
                          onChange={(e) => handleReminderChange('minutes', parseInt(e.target.value, 10))}
                          disabled={isDisabled}
                          className={cn(
                            'w-20 h-[30px] px-2 text-md border border-input rounded-sm shadow-xs transition-[color,box-shadow]',
                            'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                            'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50'
                          )}
                        >
                          {Array.from({ length: 59 }, (_, i) => i + 1).map(m => (
                            <option key={m} value={m}>{m}</option>
                          ))}
                        </select>
                        <span className="text-gray-700">{t('LBL_MINUTES_BEFORE')}</span>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Invitee selection (Events only) - ブロックスタイルで表示 */}
      {activeTab === 'Events' && availableUsers.length > 0 && (
        <div className="quickcreate-block mt-3">
          <h4 className="fieldBlockHeader font-bold leading-[1.1] mt-0 mb-2 pb-1 border-b border-gray-300">
            {t('LBL_INVITEE_BLOCK')}
          </h4>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 pr-8">
            <div className="md:col-span-2">
              <div className="flex items-start gap-2">
                <label
                  className={cn(
                    'text-md text-gray-700 flex-shrink-0 w-[110px] text-right leading-tight min-h-[54px] pt-[18px] flex items-start justify-end',
                    isDisabled && 'text-gray-400'
                  )}
                >
                  {t('LBL_INVITEES')}
                </label>
                <span className="w-3 min-h-[54px] pt-[18px] flex-shrink-0" aria-hidden="true" />
                <div className="flex-1 min-w-0 p-3 bg-gray-50 border border-gray-200 rounded-md">
              {/* Invitee search input */}
              <div className="relative" ref={inviteeInputContainerRef}>
                <input
                  type="text"
                  value={inviteeSearchTerm}
                  onChange={(e) => {
                    setInviteeSearchTerm(e.target.value);
                    setIsInviteeDropdownOpen(true);
                  }}
                  onFocus={() => {
                    updateInviteeDropdownPosition();
                    setIsInviteeDropdownOpen(true);
                  }}
                  placeholder={t('LBL_SEARCH_USERS_PLACEHOLDER')}
                  disabled={isDisabled}
                  className={cn(
                    'w-full h-[30px] px-3 border rounded-sm shadow-sm text-md',
                    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                    'disabled:bg-gray-100 disabled:text-gray-500',
                    'border-gray-300'
                  )}
                />

                {/* Dropdown (Portal経由でbody直下にレンダリング) */}
                {isInviteeDropdownOpen && !isDisabled && inviteeDropdownPosition && createPortal(
                  <div
                    ref={inviteeDropdownRef}
                    className="fixed z-[100003] bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-auto pointer-events-auto"
                    style={{
                      top: inviteeDropdownPosition.top,
                      left: inviteeDropdownPosition.left,
                      width: inviteeDropdownPosition.width
                    }}
                    onWheel={(e) => {
                      e.stopPropagation();
                      const target = e.currentTarget;
                      target.scrollTop += e.deltaY * 0.3;
                    }}
                  >
                    {filteredUsers.length > 0 ? (
                      <div className="py-1">
                        {filteredUsers
                          .filter(user => !selectedInvitees.includes(user.id))
                          .map(user => (
                            <div
                              key={user.id}
                              onClick={() => handleAddInvitee(user.id)}
                              className="px-3 py-1.5 text-md cursor-pointer hover:bg-blue-50"
                            >
                              {user.name}
                            </div>
                          ))}
                        {filteredUsers.filter(user => !selectedInvitees.includes(user.id)).length === 0 && (
                          <div className="px-3 py-1.5 text-md text-gray-500 text-center">
                            {t('LBL_ALL_USERS_SELECTED')}
                          </div>
                        )}
                      </div>
                    ) : (
                      <div className="px-3 py-1.5 text-md text-gray-500 text-center">
                        {t('LBL_NO_MATCHING_USERS')}
                      </div>
                    )}
                  </div>,
                  document.body
                )}
              </div>

              {/* Selected invitees (検索欄の下に表示) */}
                  {selectedInvitees.length > 0 && (
                    <div className="mt-2 flex flex-wrap gap-2">
                      {selectedInvitees.map(userId => {
                        const user = availableUsers.find(u => u.id === userId);
                        if (!user) return null;
                        return (
                          <div
                            key={userId}
                            className="relative inline-flex items-center bg-blue-100 text-blue-800 rounded-md text-md"
                          >
                            <span className="pl-3 pr-6 py-1.5">{user.name}</span>
                            {!isDisabled && (
                              <button
                                type="button"
                                onClick={() => handleRemoveInvitee(userId)}
                                className="absolute right-0 top-0 bottom-0 w-6 flex items-center justify-center hover:bg-blue-200 rounded-r-md transition-colors cursor-pointer"
                                aria-label={`${user.name}を削除`}
                              >
                                <X className="w-4 h-4" />
                              </button>
                            )}
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CalendarForm;
