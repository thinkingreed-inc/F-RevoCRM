import { useState, useEffect, useCallback } from 'react';
import { FieldValue } from '../../../types/field';

/**
 * useRecordData hook parameters
 */
export interface UseRecordDataParams {
  /** Module name (e.g., 'Calendar', 'Events', 'Accounts') */
  module: string;
  /** Record ID for fetching. If empty/undefined, no fetch is performed */
  recordId?: string;
  /** Skip fetching even if recordId is provided */
  skip?: boolean;
}

/**
 * useRecordData hook return value
 */
export interface UseRecordDataResult {
  /** Fetched record data */
  data: Record<string, FieldValue> | null;
  /** Loading state */
  loading: boolean;
  /** Error message if fetch failed */
  error: string | null;
  /** Refetch function to manually trigger data reload */
  refetch: () => Promise<void>;
  /** Actual module name from response (useful for Calendar/Events) */
  actualModule: string | null;
}

/**
 * Invitee info structure (returned by Events GetRecord API)
 */
interface InviteeInfo {
  id: number;
  name: string;
  status: string;
}

/**
 * GetRecord API response structure
 * The API returns data directly without a success/result wrapper
 */
interface GetRecordApiResponse {
  record: Record<string, string>;
  /** Display values for reference fields (e.g., contact_id -> "John Doe") */
  displayValues?: Record<string, string>;
  /** Invitees with details (Events only) */
  invitees?: InviteeInfo[];
  /** Selected user IDs for invitees (Events only) */
  selectedusers?: number[];
  module: string;
  recordId: string;
  timestamp: string;
  // Error response structure (when API fails)
  success?: boolean;
  error?: {
    message: string;
    code?: number;
  };
}

/**
 * Build the API URL for GetRecord
 */
function buildApiUrl(module: string, recordId: string): string {
  const baseUrl = window.location.origin + window.location.pathname;
  const params = new URLSearchParams({
    module,
    api: 'GetRecord',
    record: recordId
  });
  return `${baseUrl}?${params.toString()}`;
}

/**
 * Transform API response data for form usage
 *
 * Handles Calendar/Events specific transformations:
 * - Converts date_start + time_start to datetime-local format
 * - Converts due_date + time_end to datetime-local format
 * - Converts allday flag to is_allday boolean
 * - Removes 'label' field to prevent confusion with 'subject'
 * - Adds display values for reference fields (e.g., contact_id_display)
 * - Converts selectedusers (invitees) to string array
 * - Preserves all other fields as-is
 */
function transformRecordData(
  data: Record<string, string>,
  module: string,
  displayValues?: Record<string, string>,
  selectedusers?: number[]
): Record<string, FieldValue> {
  const transformed: Record<string, FieldValue> = { ...data };

  // Remove 'label' field to prevent it from overwriting 'subject'
  // The 'label' field contains formatted display text (e.g., "Title - (Status)")
  // but 'subject' contains the actual value we need
  delete transformed.label;

  // Calendar/Events specific transformations
  if (module === 'Calendar' || module === 'Events') {
    // Convert date_start + time_start to datetime-local format (YYYY-MM-DDTHH:MM)
    if (data.date_start && !data.date_start.includes('T')) {
      const date = data.date_start;
      const time = data.time_start || '00:00';
      // time_start may include seconds (HH:MM:SS), extract HH:MM
      transformed.date_start = `${date}T${time.substring(0, 5)}`;
    }

    // Convert due_date + time_end to datetime-local format
    if (data.due_date && !data.due_date.includes('T')) {
      const date = data.due_date;
      const time = data.time_end || '00:00';
      transformed.due_date = `${date}T${time.substring(0, 5)}`;
    }

    // Convert allday flag to is_allday boolean
    // Database stores allday as '0' or '1' string
    if (data.allday === '1' || data.allday === 1 as any) {
      transformed.is_allday = true;
    } else {
      transformed.is_allday = false;
    }

    // Add record parameter for update operations
    if (data.id || data.activityid) {
      transformed.record = data.id || data.activityid;
    }

    // Add selectedusers (invitees) as string array
    if (selectedusers && selectedusers.length > 0) {
      transformed.selectedusers = selectedusers.map(id => String(id));
    }
  }

  // Add display values for reference fields (e.g., contact_id_display)
  // These are used by ReferenceField to show the display name instead of just the ID
  if (displayValues) {
    for (const [fieldName, displayValue] of Object.entries(displayValues)) {
      transformed[`${fieldName}_display`] = displayValue;
    }
  }

  return transformed;
}

/**
 * Hook for fetching record data via GetRecord API
 *
 * This hook provides:
 * - Fetching record data by module and recordId
 * - Loading and error states
 * - Automatic data transformation for Calendar/Events
 * - Manual refetch capability
 * - Skip option for conditional fetching
 *
 * @param params - Hook parameters
 * @returns Record data fetch result
 *
 * @example
 * ```tsx
 * // Basic usage
 * const { data, loading, error } = useRecordData({
 *   module: 'Events',
 *   recordId: '12345'
 * });
 *
 * // With skip option (useful for new records)
 * const { data, loading, error } = useRecordData({
 *   module: 'Events',
 *   recordId: recordId,
 *   skip: !recordId // Skip if no recordId
 * });
 *
 * // Manual refetch
 * const { data, refetch } = useRecordData({ module: 'Events', recordId: '12345' });
 * await refetch();
 * ```
 */
export function useRecordData(params: UseRecordDataParams): UseRecordDataResult {
  const { module, recordId, skip = false } = params;

  // Initialize loading to true if we have a recordId and not skipping
  // This prevents race conditions where the effect runs before fetch starts
  const shouldFetch = !!(module && recordId && !skip);
  const [data, setData] = useState<Record<string, FieldValue> | null>(null);
  const [loading, setLoading] = useState<boolean>(shouldFetch);
  const [error, setError] = useState<string | null>(null);
  const [actualModule, setActualModule] = useState<string | null>(null);

  /**
   * Fetch record data from GetRecord API
   */
  const fetchRecordData = useCallback(async () => {
    // Skip if no module or recordId, or if skip flag is set
    if (!module || !recordId || skip) {
      setData(null);
      setLoading(false);
      setError(null);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const url = buildApiUrl(module, recordId);
      const response = await fetch(url, {
        method: 'GET',
        credentials: 'include', // Include cookies for authentication
        headers: {
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result: GetRecordApiResponse = await response.json();

      // Check for error response (success: false with error object)
      if (result.success === false || result.error) {
        throw new Error(result.error?.message || 'レコードの取得に失敗しました');
      }

      // API returns record directly (not wrapped in result)
      if (!result.record) {
        throw new Error('レコードデータが見つかりません');
      }

      const { record, displayValues, selectedusers, module: responseModule } = result;

      // Transform data for form usage
      const transformedData = transformRecordData(record, responseModule || module, displayValues, selectedusers);

      setData(transformedData);
      setActualModule(responseModule || module);
      setError(null);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'レコードの取得に失敗しました';
      setError(errorMessage);
      setData(null);
      setActualModule(null);
      console.error('[useRecordData] Error fetching record:', err);
    } finally {
      setLoading(false);
    }
  }, [module, recordId, skip]);

  /**
   * Refetch function for manual data reload
   */
  const refetch = useCallback(async () => {
    await fetchRecordData();
  }, [fetchRecordData]);

  /**
   * Fetch data when module or recordId changes
   */
  useEffect(() => {
    fetchRecordData();
  }, [fetchRecordData]);

  return {
    data,
    loading,
    error,
    refetch,
    actualModule
  };
}

export default useRecordData;
