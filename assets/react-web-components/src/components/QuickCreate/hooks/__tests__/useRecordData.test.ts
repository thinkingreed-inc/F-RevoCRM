import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor, act } from '@testing-library/react';
import { useRecordData } from '../useRecordData';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('useRecordData', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Set up window.location for URL building
    Object.defineProperty(window, 'location', {
      value: {
        origin: 'http://localhost',
        pathname: '/frevocrm_webcomponents/index.php'
      },
      writable: true
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('初期状態', () => {
    it('recordIdが空の場合はフェッチしない', () => {
      const { result } = renderHook(() =>
        useRecordData({ module: 'Accounts', recordId: '' })
      );

      expect(result.current.data).toBeNull();
      expect(result.current.loading).toBe(false);
      expect(result.current.error).toBeNull();
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('moduleが空の場合はフェッチしない', () => {
      const { result } = renderHook(() =>
        useRecordData({ module: '', recordId: '123' })
      );

      expect(result.current.data).toBeNull();
      expect(result.current.loading).toBe(false);
      expect(result.current.error).toBeNull();
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('skip=trueの場合はフェッチしない', () => {
      const { result } = renderHook(() =>
        useRecordData({ module: 'Accounts', recordId: '123', skip: true })
      );

      expect(result.current.data).toBeNull();
      expect(result.current.loading).toBe(false);
      expect(result.current.error).toBeNull();
      expect(mockFetch).not.toHaveBeenCalled();
    });
  });

  describe('データ取得', () => {
    it('正常にレコードデータを取得できる', async () => {
      // API returns data directly without success/result wrapper
      const mockResponse = {
        record: {
          id: '123',
          accountname: 'Test Account',
          phone: '03-1234-5678'
        },
        module: 'Accounts',
        recordId: '123',
        timestamp: '2026-01-22 10:00:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Accounts', recordId: '123' })
      );

      // Initially loading
      expect(result.current.loading).toBe(true);

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.data).toEqual({
        id: '123',
        accountname: 'Test Account',
        phone: '03-1234-5678'
      });
      expect(result.current.error).toBeNull();
      expect(result.current.actualModule).toBe('Accounts');
    });

    it('Calendar/Eventsの日時データを変換する', async () => {
      // API returns data directly without success/result wrapper
      const mockResponse = {
        record: {
          id: '456',
          subject: 'Meeting',
          date_start: '2026-01-22',
          time_start: '14:30:00',
          due_date: '2026-01-22',
          time_end: '15:30:00',
          allday: '0',
          activitytype: 'Meeting'
        },
        module: 'Events',
        recordId: '456',
        timestamp: '2026-01-22 10:00:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '456' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.data).toMatchObject({
        subject: 'Meeting',
        date_start: '2026-01-22T14:30',
        due_date: '2026-01-22T15:30',
        is_allday: false
      });
    });

    it('Picklistフィールド（activitytype等）が変換後のデータに含まれる', async () => {
      // API returns data directly without success/result wrapper
      const mockResponse = {
        record: {
          id: '789',
          subject: 'テスト活動',
          date_start: '2026-01-22',
          time_start: '10:00:00',
          due_date: '2026-01-22',
          time_end: '11:00:00',
          allday: '0',
          activitytype: 'Call',
          eventstatus: 'Planned',
          visibility: 'Public'
        },
        module: 'Events',
        recordId: '789',
        timestamp: '2026-01-22 10:00:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '789' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // Picklistフィールドが変換後のデータに含まれていることを確認
      expect(result.current.data).toMatchObject({
        activitytype: 'Call',
        eventstatus: 'Planned',
        visibility: 'Public'
      });
    });

    it('カスタムフィールド（cf_XXXX）が変換後のデータに含まれる', async () => {
      // API returns data directly without success/result wrapper
      const mockResponse = {
        record: {
          id: '101',
          subject: 'カスタムフィールドテスト',
          date_start: '2026-01-22',
          time_start: '09:00:00',
          due_date: '2026-01-22',
          time_end: '10:00:00',
          allday: '0',
          activitytype: 'Meeting',
          cf_1030: 'カスタム値1',
          cf_1031: 'カスタム値2',
          cf_1032: '選択肢1'
        },
        module: 'Events',
        recordId: '101',
        timestamp: '2026-01-22 10:00:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '101' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // カスタムフィールドが変換後のデータに含まれていることを確認
      expect(result.current.data).toMatchObject({
        cf_1030: 'カスタム値1',
        cf_1031: 'カスタム値2',
        cf_1032: '選択肢1'
      });
    });

    it('終日イベントのalldayフラグを正しく変換する', async () => {
      // API returns data directly without success/result wrapper
      const mockResponse = {
        record: {
          id: '789',
          subject: 'All Day Event',
          date_start: '2026-01-22',
          time_start: '00:00:00',
          due_date: '2026-01-22',
          time_end: '00:00:00',
          allday: '1'
        },
        module: 'Events',
        recordId: '789',
        timestamp: '2026-01-22 10:00:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '789' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.data?.is_allday).toBe(true);
    });

    it('labelフィールドを削除してsubjectを保持する', async () => {
      // API returns data directly without success/result wrapper
      // label field contains formatted text (e.g., "Title - (Status)")
      // which should not overwrite subject
      const mockResponse = {
        record: {
          id: '999',
          subject: 'テスト活動',
          label: 'テスト活動 - (計画済み)',  // This should be removed
          date_start: '2026-01-22',
          time_start: '14:00:00',
          due_date: '2026-01-22',
          time_end: '15:00:00',
          allday: '0',
          activitytype: 'Call'
        },
        module: 'Events',
        recordId: '999',
        timestamp: '2026-01-22 10:00:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '999' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // label should be removed, subject should be preserved
      expect(result.current.data?.subject).toBe('テスト活動');
      expect(result.current.data?.label).toBeUndefined();
    });

    it('displayValuesが参照フィールドの表示ラベルとして変換される', async () => {
      // API returns data with displayValues for reference fields
      const mockResponse = {
        record: {
          id: '200',
          subject: '参照テスト活動',
          date_start: '2026-01-22',
          time_start: '10:00:00',
          due_date: '2026-01-22',
          time_end: '11:00:00',
          allday: '0',
          activitytype: 'Meeting',
          contact_id: '53',
          parent_id: '85'
        },
        displayValues: {
          contact_id: '山田太郎',
          parent_id: 'テスト株式会社'
        },
        module: 'Events',
        recordId: '200',
        timestamp: '2026-01-22 10:00:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '200' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      // displayValuesが _display サフィックス付きのフィールドとして変換される
      expect(result.current.data?.contact_id).toBe('53');
      expect(result.current.data?.contact_id_display).toBe('山田太郎');
      expect(result.current.data?.parent_id).toBe('85');
      expect(result.current.data?.parent_id_display).toBe('テスト株式会社');
    });
  });

  describe('エラーハンドリング', () => {
    it('APIエラー時にエラーメッセージを設定する', async () => {
      const mockResponse = {
        success: false,
        error: {
          message: 'レコードが見つかりません'
        }
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Accounts', recordId: '999' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.error).toBe('レコードが見つかりません');
      expect(result.current.data).toBeNull();
    });

    it('HTTPエラー時にエラーを設定する', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Accounts', recordId: '999' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.error).toContain('HTTP error');
      expect(result.current.data).toBeNull();
    });

    it('ネットワークエラー時にエラーを設定する', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      const { result } = renderHook(() =>
        useRecordData({ module: 'Accounts', recordId: '123' })
      );

      await waitFor(() => {
        expect(result.current.loading).toBe(false);
      });

      expect(result.current.error).toBe('Network error');
      expect(result.current.data).toBeNull();
    });
  });

  describe('refetch', () => {
    it('refetch関数でデータを再取得できる', async () => {
      // API returns data directly without success/result wrapper
      const mockResponse1 = {
        record: { id: '123', accountname: 'Old Name' },
        module: 'Accounts',
        recordId: '123',
        timestamp: '2026-01-22 10:00:00'
      };

      const mockResponse2 = {
        record: { id: '123', accountname: 'New Name' },
        module: 'Accounts',
        recordId: '123',
        timestamp: '2026-01-22 10:01:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse1)
      });

      const { result } = renderHook(() =>
        useRecordData({ module: 'Accounts', recordId: '123' })
      );

      await waitFor(() => {
        expect(result.current.data?.accountname).toBe('Old Name');
      });

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse2)
      });

      await act(async () => {
        await result.current.refetch();
      });

      expect(result.current.data?.accountname).toBe('New Name');
    });
  });

  describe('パラメータ変更', () => {
    it('recordIdが変更されると再フェッチする', async () => {
      // API returns data directly without success/result wrapper
      const mockResponse1 = {
        record: { id: '123', accountname: 'Account 1' },
        module: 'Accounts',
        recordId: '123',
        timestamp: '2026-01-22 10:00:00'
      };

      const mockResponse2 = {
        record: { id: '456', accountname: 'Account 2' },
        module: 'Accounts',
        recordId: '456',
        timestamp: '2026-01-22 10:01:00'
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse1)
      });

      const { result, rerender } = renderHook(
        ({ recordId }) => useRecordData({ module: 'Accounts', recordId }),
        { initialProps: { recordId: '123' } }
      );

      await waitFor(() => {
        expect(result.current.data?.accountname).toBe('Account 1');
      });

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse2)
      });

      rerender({ recordId: '456' });

      await waitFor(() => {
        expect(result.current.data?.accountname).toBe('Account 2');
      });

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  describe('初期loading状態', () => {
    it('recordIdが存在する場合、初期状態でloadingがtrue', () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolve

      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '123' })
      );

      // Should start with loading=true when recordId is provided
      expect(result.current.loading).toBe(true);
    });

    it('recordIdが空の場合、初期状態でloadingがfalse', () => {
      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '' })
      );

      // Should start with loading=false when no recordId
      expect(result.current.loading).toBe(false);
    });

    it('skip=trueの場合、初期状態でloadingがfalse', () => {
      const { result } = renderHook(() =>
        useRecordData({ module: 'Events', recordId: '123', skip: true })
      );

      // Should start with loading=false when skip is true
      expect(result.current.loading).toBe(false);
    });
  });
});
