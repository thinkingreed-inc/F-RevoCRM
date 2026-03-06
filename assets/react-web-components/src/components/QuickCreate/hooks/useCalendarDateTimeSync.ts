import { useRef, useEffect, useCallback } from 'react';
import { FieldValue } from '../../../types/field';

/**
 * Activity type: Calendar (ToDo) or Events (Call/Meeting)
 */
type ActivityType = 'Calendar' | 'Events';

/**
 * Options for useCalendarDateTimeSync hook
 */
export interface UseCalendarDateTimeSyncOptions {
  /** Current form data */
  formData: Record<string, FieldValue>;
  /** Callback to update specific field */
  onFieldChange: (fieldName: string, value: FieldValue) => void;
  /** Default duration for Call activities (minutes) */
  defaultCallDuration: number;
  /** Default duration for other events (minutes) */
  defaultOtherEventDuration: number;
  /** Current activity type */
  activeTab: ActivityType;
  /** Whether in edit mode */
  isEditMode: boolean;
  /** Whether all-day is checked */
  isAllDay: boolean;
  /** Parse datetime value to date and time */
  parseDateTimeValue: (value: string | undefined) => { date: string; time: string };
  /** Combine date and time to datetime value */
  combineDateTimeValue: (date: string, time: string) => string;
}

/**
 * Return value from useCalendarDateTimeSync hook
 */
export interface UseCalendarDateTimeSyncReturn {
  /** Handler for date_start change */
  handleDateStartChange: (newValue: string) => void;
  /** Handler for time_start change (from TimeComboBox) - currentDate is the current date value from UI */
  handleTimeStartChange: (newTime: string, currentDate: string) => void;
  /** Handler for due_date change */
  handleDueDateChange: (newValue: string) => void;
  /** Handler for time_end change (from TimeComboBox) - currentDate is the current date value from UI */
  handleTimeEndChange: (newTime: string, currentDate: string) => void;
  /** Handler for datetime field focus (enables sync in edit mode) */
  handleDateTimeFieldFocus: () => void;
}

/**
 * Hook to synchronize end datetime when start datetime changes
 *
 * This replicates the behavior from the legacy Calendar/resources/Edit.js:
 * - When start datetime changes, end datetime is automatically calculated
 * - Uses user's default duration settings (callduration/othereventduration)
 * - If user manually edits end datetime, remembers the time difference
 * - In edit mode, sync is only enabled after user focuses on a datetime field
 */
export function useCalendarDateTimeSync({
  formData,
  onFieldChange,
  defaultCallDuration,
  defaultOtherEventDuration,
  activeTab,
  isEditMode,
  isAllDay,
  parseDateTimeValue,
  combineDateTimeValue,
}: UseCalendarDateTimeSyncOptions): UseCalendarDateTimeSyncReturn {
  // User-modified time difference (minutes) - replaces default duration when set
  const userChangedDurationRef = useRef<number | null>(null);

  // Whether sync is enabled (false in edit mode until user focuses on datetime field)
  const syncEnabledRef = useRef<boolean>(!isEditMode);

  // Flag to prevent recursive updates
  const isUpdatingRef = useRef<boolean>(false);

  /**
   * Get effective duration based on activity type and user edits
   */
  const getEffectiveDuration = useCallback((): number => {
    // If user has manually edited end time, use their duration
    if (userChangedDurationRef.current !== null) {
      return userChangedDurationRef.current;
    }

    // Use default based on activity type
    return activeTab === 'Events' ? defaultOtherEventDuration : defaultCallDuration;
  }, [activeTab, defaultOtherEventDuration, defaultCallDuration]);

  /**
   * Calculate datetime from date and time strings
   */
  const parseToDate = useCallback((dateStr: string, timeStr: string): Date | null => {
    if (!dateStr) return null;
    const time = timeStr || '00:00';
    const [hours, minutes] = time.split(':').map(Number);
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return null;
    date.setHours(hours || 0, minutes || 0, 0, 0);
    return date;
  }, []);

  /**
   * Format date to YYYY-MM-DD
   */
  const formatDate = useCallback((date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }, []);

  /**
   * Format time to HH:MM
   */
  const formatTime = useCallback((date: Date): string => {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
  }, []);

  /**
   * Calculate and update end datetime based on start datetime
   */
  const updateEndDateTime = useCallback((startDate: string, startTime: string) => {
    if (isUpdatingRef.current) return;
    if (!syncEnabledRef.current) return;
    if (isAllDay) return; // Skip time sync for all-day events

    const startDateTime = parseToDate(startDate, startTime);
    if (!startDateTime) return;

    const durationMinutes = getEffectiveDuration();
    const endDateTime = new Date(startDateTime.getTime() + durationMinutes * 60 * 1000);

    const newEndDate = formatDate(endDateTime);
    const newEndTime = formatTime(endDateTime);
    const newDueDate = combineDateTimeValue(newEndDate, newEndTime);

    isUpdatingRef.current = true;
    onFieldChange('due_date', newDueDate);
    isUpdatingRef.current = false;
  }, [isAllDay, parseToDate, getEffectiveDuration, formatDate, formatTime, combineDateTimeValue, onFieldChange]);

  /**
   * Handle date_start change
   */
  const handleDateStartChange = useCallback((newValue: string) => {
    onFieldChange('date_start', newValue);

    if (!syncEnabledRef.current) return;

    const { date: newDate, time } = parseDateTimeValue(newValue);
    const currentTime = time || parseDateTimeValue(formData['date_start'] as string | undefined).time;

    updateEndDateTime(newDate, currentTime);
  }, [onFieldChange, parseDateTimeValue, formData, updateEndDateTime]);

  /**
   * Handle time_start change (from TimeComboBox)
   * @param newTime - The new time value
   * @param currentDate - The current date value from UI (to avoid stale formData)
   */
  const handleTimeStartChange = useCallback((newTime: string, currentDate: string) => {
    const newValue = combineDateTimeValue(currentDate, newTime);
    onFieldChange('date_start', newValue);

    if (!syncEnabledRef.current) return;

    updateEndDateTime(currentDate, newTime);
  }, [combineDateTimeValue, onFieldChange, updateEndDateTime]);

  /**
   * Handle due_date change
   */
  const handleDueDateChange = useCallback((newValue: string) => {
    onFieldChange('due_date', newValue);

    // Don't update user duration on programmatic changes
    if (isUpdatingRef.current) return;
    if (!syncEnabledRef.current) return;

    // Calculate new duration from user's edit
    const startValue = formData['date_start'] as string | undefined;
    const { date: startDate, time: startTime } = parseDateTimeValue(startValue);
    const { date: endDate, time: endTime } = parseDateTimeValue(newValue);

    const startDateTime = parseToDate(startDate, startTime);
    const endDateTime = parseToDate(endDate, endTime);

    if (startDateTime && endDateTime) {
      const diffMinutes = Math.round((endDateTime.getTime() - startDateTime.getTime()) / (60 * 1000));
      if (diffMinutes > 0) {
        userChangedDurationRef.current = diffMinutes;
      }
    }
  }, [onFieldChange, formData, parseDateTimeValue, parseToDate]);

  /**
   * Handle time_end change (from TimeComboBox)
   * @param newTime - The new time value
   * @param currentDate - The current date value from UI (to avoid stale formData)
   */
  const handleTimeEndChange = useCallback((newTime: string, currentDate: string) => {
    const newValue = combineDateTimeValue(currentDate, newTime);
    onFieldChange('due_date', newValue);

    // Don't update user duration on programmatic changes
    if (isUpdatingRef.current) return;
    if (!syncEnabledRef.current) return;

    // Calculate new duration from user's edit
    const startValue = formData['date_start'] as string | undefined;
    const { date: startDate, time: startTime } = parseDateTimeValue(startValue);

    const startDateTime = parseToDate(startDate, startTime);
    const endDateTime = parseToDate(currentDate, newTime);

    if (startDateTime && endDateTime) {
      const diffMinutes = Math.round((endDateTime.getTime() - startDateTime.getTime()) / (60 * 1000));
      if (diffMinutes > 0) {
        userChangedDurationRef.current = diffMinutes;
      }
    }
  }, [formData, combineDateTimeValue, onFieldChange, parseDateTimeValue, parseToDate]);

  /**
   * Handle datetime field focus - enables sync in edit mode
   */
  const handleDateTimeFieldFocus = useCallback(() => {
    if (!syncEnabledRef.current && isEditMode) {
      syncEnabledRef.current = true;
    }
  }, [isEditMode]);

  /**
   * Reset user duration when activity type changes
   */
  useEffect(() => {
    userChangedDurationRef.current = null;
  }, [activeTab]);

  /**
   * Initialize sync state based on edit mode
   */
  useEffect(() => {
    syncEnabledRef.current = !isEditMode;
  }, [isEditMode]);

  return {
    handleDateStartChange,
    handleTimeStartChange,
    handleDueDateChange,
    handleTimeEndChange,
    handleDateTimeFieldFocus,
  };
}

export default useCalendarDateTimeSync;
