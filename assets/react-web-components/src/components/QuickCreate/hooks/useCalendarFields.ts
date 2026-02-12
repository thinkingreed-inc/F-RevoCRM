import { useMemo, useCallback } from 'react';
import { FieldInfo } from '../../../types/field';
import { useQuickCreateFields } from './useQuickCreateFields';

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
 * Parsed date and time components
 */
interface DateTimeComponents {
  date: string;
  time: string;
}

/**
 * Parsed reminder components (days, hours, minutes)
 */
interface ReminderComponents {
  days: number;
  hours: number;
  minutes: number;
}

/**
 * useCalendarFields hook parameters
 */
export interface UseCalendarFieldsParams {
  /** Active tab: Calendar or Events */
  activeTab: ActivityType;
  /** Initial data for the form (optional) */
  initialData?: Record<string, any>;
  /** Record ID for edit mode (optional) */
  recordId?: string;
  /** RecordType fields for filtering (optional) */
  recordTypeFields?: Record<string, string>;
}

/**
 * useCalendarFields hook return value
 */
export interface UseCalendarFieldsResult {
  /** Calendar (ToDo) fields */
  calendarFields: FieldInfo[];
  /** Events fields */
  eventsFields: FieldInfo[];
  /** Current active tab's fields */
  currentFields: FieldInfo[];
  /** Loading state for Calendar fields */
  calendarLoading: boolean;
  /** Loading state for Events fields */
  eventsLoading: boolean;
  /** Overall loading state */
  loading: boolean;
  /** Calendar fields error */
  calendarError: string | null;
  /** Events fields error */
  eventsError: string | null;
  /** Overall error state */
  error: string | null;
  /** Edit view URL for Calendar */
  calendarEditUrl: string | null;
  /** Edit view URL for Events */
  eventsEditUrl: string | null;
  /** Current tab's edit view URL */
  editViewUrl: string | null;
  /** Available users for invitee selection (Events only) */
  availableUsers: UserInfo[];
  /** Time options in 10-minute intervals */
  timeOptions: TimeOption[];
  /** Parse datetime-local value into date and time components */
  parseDateTimeValue: (value: string | undefined) => DateTimeComponents;
  /** Combine date and time into datetime-local format */
  combineDateTimeValue: (date: string, time: string) => string;
  /** Parse reminder value (minutes) into days/hours/minutes */
  parseReminderValue: (value: any) => ReminderComponents;
  /** Combine days/hours/minutes into total minutes */
  combineReminderValue: (days: number, hours: number, minutes: number) => number;
  /** Transform initial data for edit mode */
  transformInitialDataForEdit: (
    data: Record<string, any>,
    targetTab: ActivityType
  ) => Record<string, any>;
}

/**
 * Hook for managing Calendar/Events specific field handling
 *
 * This hook provides:
 * - Fetching and managing both Calendar and Events fields
 * - Returning current fields based on active tab
 * - Transforming datetime fields for datetime-local input format
 * - Handling allday flag conversion
 * - Getting available users for invitee selection
 * - Generating 10-minute interval time options
 * - Helper functions for parsing/combining datetime and reminder values
 *
 * @param params - Hook parameters
 * @returns Calendar fields management result
 *
 * @example
 * ```tsx
 * const {
 *   currentFields,
 *   availableUsers,
 *   timeOptions,
 *   parseDateTimeValue,
 *   combineDateTimeValue,
 *   transformInitialDataForEdit
 * } = useCalendarFields({
 *   activeTab: 'Events',
 *   initialData: { subject: 'Meeting' },
 *   recordId: '123'
 * });
 * ```
 */
export function useCalendarFields(
  params: UseCalendarFieldsParams
): UseCalendarFieldsResult {
  const { activeTab, recordId, recordTypeFields } = params;

  // Fetch both Calendar and Events fields with RecordType filtering
  const {
    fields: calendarFields,
    loading: calendarLoading,
    error: calendarError,
    editViewUrl: calendarEditUrl
  } = useQuickCreateFields('Calendar', recordTypeFields);

  const {
    fields: eventsFields,
    loading: eventsLoading,
    error: eventsError,
    editViewUrl: eventsEditUrl
  } = useQuickCreateFields('Events', recordTypeFields);

  // Current tab's fields and edit URL
  const currentFields = activeTab === 'Calendar' ? calendarFields : eventsFields;
  const editViewUrl = activeTab === 'Calendar' ? calendarEditUrl : eventsEditUrl;

  // Overall loading and error states
  const loading = calendarLoading || eventsLoading;
  const error = calendarError || eventsError;

  /**
   * Get available users for invitee selection (Events only)
   * Extracts user information from assigned_user_id field's picklist values
   */
  const availableUsers = useMemo((): UserInfo[] => {
    const users: UserInfo[] = [];
    const assignedUserField = eventsFields.find(f => f.name === 'assigned_user_id');

    if (assignedUserField?.fieldinfo?.picklistvalues) {
      const picklistValues = assignedUserField.fieldinfo
        .picklistvalues as Record<string, Record<string, string> | undefined>;

      // Get users from the "Users" or "ユーザー" group (supports both English and Japanese)
      // Groups are excluded from invitees
      const userGroup = picklistValues['Users'] || picklistValues['ユーザー'];
      if (userGroup && typeof userGroup === 'object') {
        Object.entries(userGroup).forEach(([id, name]) => {
          users.push({ id, name });
        });
      }
    }

    return users;
  }, [eventsFields]);

  /**
   * Generate time options in 10-minute intervals (00:00 - 23:50)
   */
  const timeOptions = useMemo((): TimeOption[] => {
    const options: TimeOption[] = [];
    for (let h = 0; h < 24; h++) {
      for (let m = 0; m < 60; m += 10) {
        const hh = h.toString().padStart(2, '0');
        const mm = m.toString().padStart(2, '0');
        options.push({ value: `${hh}:${mm}`, label: `${hh}:${mm}` });
      }
    }
    return options;
  }, []);

  /**
   * Parse datetime-local value (YYYY-MM-DDTHH:MM) into date and time components
   * Rounds time to nearest 10-minute interval (5+ rounds up) for best approximation
   *
   * @param value - Datetime-local value
   * @returns Parsed date and time components
   *
   * @example
   * parseDateTimeValue('2026-01-15T14:14') // => { date: '2026-01-15', time: '14:10' } (rounded down)
   * parseDateTimeValue('2026-01-15T14:15') // => { date: '2026-01-15', time: '14:20' } (rounded up)
   * parseDateTimeValue('2026-01-15T14:20') // => { date: '2026-01-15', time: '14:20' } (exact match)
   */
  const parseDateTimeValue = useCallback(
    (value: string | undefined): DateTimeComponents => {
      if (!value) {
        return { date: '', time: '' };
      }

      // Expected format: YYYY-MM-DDTHH:MM
      if (value.includes('T')) {
        const [datePart, timePart] = value.split('T');

        // Round time to nearest 10-minute interval (5+ rounds up)
        let roundedTime = timePart || '';
        if (roundedTime) {
          const [hh, mm] = roundedTime.split(':');
          let hours = parseInt(hh, 10);
          const minutes = parseInt(mm, 10);
          // Round to nearest 10 minutes (14 -> 10, 15 -> 20, 25 -> 30)
          const roundedMinutes = Math.round(minutes / 10) * 10;

          // Handle overflow when rounding 55-59 minutes to 60
          if (roundedMinutes === 60) {
            hours = (hours + 1) % 24;
            roundedTime = `${hours.toString().padStart(2, '0')}:00`;
          } else {
            roundedTime = `${hours.toString().padStart(2, '0')}:${roundedMinutes.toString().padStart(2, '0')}`;
          }
        }

        return { date: datePart, time: roundedTime };
      }

      // If no 'T' separator, treat as date only
      return { date: value, time: '' };
    },
    []
  );

  /**
   * Combine date and time into datetime-local format (YYYY-MM-DDTHH:MM)
   *
   * @param date - Date part (YYYY-MM-DD)
   * @param time - Time part (HH:MM)
   * @returns Combined datetime-local value
   *
   * @example
   * combineDateTimeValue('2026-01-15', '14:30') // => '2026-01-15T14:30'
   */
  const combineDateTimeValue = useCallback((date: string, time: string): string => {
    if (!date) return '';
    if (!time) return date;
    return `${date}T${time}`;
  }, []);

  /**
   * Parse reminder value (total minutes) into days, hours, and minutes components
   *
   * @param value - Total minutes for reminder
   * @returns Parsed reminder components
   *
   * @example
   * parseReminderValue(1455) // => { days: 1, hours: 0, minutes: 15 }
   */
  const parseReminderValue = useCallback((value: any): ReminderComponents => {
    if (!value || value === '' || value === 0 || value === '0') {
      return { days: 0, hours: 0, minutes: 0 };
    }

    const totalMinutes = parseInt(String(value), 10);
    const days = Math.floor(totalMinutes / (24 * 60));
    const hours = Math.floor((totalMinutes - days * 24 * 60) / 60);
    const minutes = totalMinutes % 60;

    return { days, hours, minutes };
  }, []);

  /**
   * Combine days, hours, and minutes into total minutes
   *
   * @param days - Number of days
   * @param hours - Number of hours
   * @param minutes - Number of minutes
   * @returns Total minutes
   *
   * @example
   * combineReminderValue(1, 0, 15) // => 1455
   */
  const combineReminderValue = useCallback(
    (days: number, hours: number, minutes: number): number => {
      return days * 24 * 60 + hours * 60 + minutes;
    },
    []
  );

  /**
   * Transform initial data for edit mode
   * Converts date/time fields to datetime-local format and handles allday flag
   *
   * @param data - Initial data from Calendar/Events record
   * @param targetTab - Target tab (Calendar or Events)
   * @returns Transformed data for form
   *
   * @example
   * transformInitialDataForEdit({
   *   date_start: '2026-01-15',
   *   time_start: '14:30:00',
   *   allday: '1'
   * }, 'Events')
   * // => { date_start: '2026-01-15T14:30', is_allday: true, ... }
   */
  const transformInitialDataForEdit = useCallback(
    (data: Record<string, any>, _targetTab: ActivityType): Record<string, any> => {
      const transformed: Record<string, any> = { ...data };

      // Convert date_start + time_start to datetime-local format
      if (data.date_start && !data.date_start.includes('T')) {
        const date = data.date_start;
        const time = data.time_start || '00:00';
        transformed.date_start = `${date}T${time.substring(0, 5)}`;
      }

      // Convert due_date + time_end to datetime-local format
      if (data.due_date && !data.due_date.includes('T')) {
        const date = data.due_date;
        const time = data.time_end || '00:00';
        transformed.due_date = `${date}T${time.substring(0, 5)}`;
      }

      // Convert allday flag (1, '1', or is_allday) to boolean is_allday
      if (data.allday === '1' || data.allday === 1 || data.is_allday) {
        transformed.is_allday = true;
      }

      // Add record parameter for update
      if (recordId) {
        transformed.record = recordId;
      }

      return transformed;
    },
    [recordId]
  );

  return {
    calendarFields,
    eventsFields,
    currentFields,
    calendarLoading,
    eventsLoading,
    loading,
    calendarError,
    eventsError,
    error,
    calendarEditUrl,
    eventsEditUrl,
    editViewUrl,
    availableUsers,
    timeOptions,
    parseDateTimeValue,
    combineDateTimeValue,
    parseReminderValue,
    combineReminderValue,
    transformInitialDataForEdit
  };
}

export default useCalendarFields;
