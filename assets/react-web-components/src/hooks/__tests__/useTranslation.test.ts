/**
 * useTranslation フックのテスト
 */

import { renderHook } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { useTranslation } from '../useTranslation';
import { MockTranslationProvider, DEFAULT_TRANSLATIONS } from '../../contexts/TranslationContext';
import { ReactNode } from 'react';

// TranslationProvider でラップするヘルパー
function createWrapper(translations?: Record<string, string>) {
  return function Wrapper({ children }: { children: ReactNode }) {
    return MockTranslationProvider({ children, translations });
  };
}

describe('useTranslation', () => {
  describe('t 関数', () => {
    it('存在するキーの翻訳を返す', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({ LBL_SAVE: 'テスト保存' }),
      });

      expect(result.current.t('LBL_SAVE')).toBe('テスト保存');
    });

    it('存在しないキーはキーそのものを返す', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper(),
      });

      expect(result.current.t('NON_EXISTENT_KEY')).toBe('NON_EXISTENT_KEY');
    });

    it('%s プレースホルダーを1つ置換', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({ LBL_FIELD_REQUIRED: '%sは必須です' }),
      });

      expect(result.current.t('LBL_FIELD_REQUIRED', 'タイトル')).toBe('タイトルは必須です');
    });

    it('%s プレースホルダーを複数置換', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({ LBL_FIELD_MAX_LENGTH: '%sは%s文字以内で入力してください' }),
      });

      expect(result.current.t('LBL_FIELD_MAX_LENGTH', 'タイトル', '100')).toBe(
        'タイトルは100文字以内で入力してください'
      );
    });

    it('数値を引数として渡せる', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({ LBL_INVITEES_SELECTED: '%s名選択中' }),
      });

      expect(result.current.t('LBL_INVITEES_SELECTED', 5)).toBe('5名選択中');
    });

    it('引数が足りない場合は %s が残る', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({ LBL_TEST: '%sと%sと%s' }),
      });

      expect(result.current.t('LBL_TEST', 'A', 'B')).toBe('AとBと%s');
    });

    it('引数が多い場合は無視される', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({ LBL_TEST: '%s' }),
      });

      expect(result.current.t('LBL_TEST', 'A', 'B', 'C')).toBe('A');
    });
  });

  describe('isLoading', () => {
    it('MockTranslationProvider 使用時は false', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper(),
      });

      expect(result.current.isLoading).toBe(false);
    });
  });

  describe('error', () => {
    it('正常時は null', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper(),
      });

      expect(result.current.error).toBeNull();
    });
  });

  describe('language', () => {
    it('デフォルト言語を返す', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper(),
      });

      expect(result.current.language).toBe('ja_jp');
    });
  });

  describe('translations', () => {
    it('翻訳データにアクセス可能', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({ LBL_CUSTOM: 'カスタム値' }),
      });

      expect(result.current.translations.LBL_CUSTOM).toBe('カスタム値');
    });

    it('DEFAULT_TRANSLATIONS が含まれる', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper(),
      });

      expect(result.current.translations.LBL_SAVE).toBe(DEFAULT_TRANSLATIONS.LBL_SAVE);
      expect(result.current.translations.LBL_CANCEL).toBe(DEFAULT_TRANSLATIONS.LBL_CANCEL);
    });
  });

  describe('refetch', () => {
    it('refetch 関数が存在する', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper(),
      });

      expect(typeof result.current.refetch).toBe('function');
    });
  });

  describe('Provider 外での使用', () => {
    it('Provider なしで使用するとエラー', () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

      expect(() => {
        renderHook(() => useTranslation());
      }).toThrow('useTranslationContext must be used within a TranslationProvider');

      consoleError.mockRestore();
    });
  });

  describe('実際のユースケース', () => {
    it('バリデーションメッセージの生成', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({
          LBL_FIELD_REQUIRED: '%sは必須です',
          LBL_END_DATE_AFTER_START: '終了日時は開始日時より後に設定してください',
        }),
      });

      const { t } = result.current;

      // 必須フィールドエラー
      expect(t('LBL_FIELD_REQUIRED', 'タイトル')).toBe('タイトルは必須です');
      expect(t('LBL_FIELD_REQUIRED', '開始日時')).toBe('開始日時は必須です');

      // 日付バリデーションエラー
      expect(t('LBL_END_DATE_AFTER_START')).toBe('終了日時は開始日時より後に設定してください');
    });

    it('成功メッセージの生成', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({
          LBL_CREATED_SUCCESS: '%sを作成しました',
          LBL_UPDATED_SUCCESS: '%sを更新しました',
        }),
      });

      const { t } = result.current;

      expect(t('LBL_CREATED_SUCCESS', 'ToDo')).toBe('ToDoを作成しました');
      expect(t('LBL_UPDATED_SUCCESS', '活動')).toBe('活動を更新しました');
    });

    it('招待者選択UIの文字列', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({
          LBL_INVITEES: '招待者',
          LBL_INVITEES_SELECTED: '%s名選択中',
          LBL_CLEAR_ALL: 'すべてクリア',
          LBL_ALL_USERS_SELECTED: 'すべてのユーザーが選択済みです',
          LBL_NO_MATCHING_USERS: '該当するユーザーがいません',
        }),
      });

      const { t } = result.current;

      expect(t('LBL_INVITEES')).toBe('招待者');
      expect(t('LBL_INVITEES_SELECTED', 3)).toBe('3名選択中');
      expect(t('LBL_CLEAR_ALL')).toBe('すべてクリア');
      expect(t('LBL_ALL_USERS_SELECTED')).toBe('すべてのユーザーが選択済みです');
      expect(t('LBL_NO_MATCHING_USERS')).toBe('該当するユーザーがいません');
    });

    it('リマインダー設定UIの文字列', () => {
      const { result } = renderHook(() => useTranslation(), {
        wrapper: createWrapper({
          LBL_SEND_NOTIFICATION: '事前にメールを送信',
          LBL_SET_REMINDER: '設定する',
          LBL_START: '開始',
          LBL_DAYS: '日',
          LBL_HOURS: '時間',
          LBL_MINUTES_BEFORE: '分前に通知',
        }),
      });

      const { t } = result.current;

      expect(t('LBL_SEND_NOTIFICATION')).toBe('事前にメールを送信');
      expect(t('LBL_SET_REMINDER')).toBe('設定する');
      expect(t('LBL_START')).toBe('開始');
      expect(t('LBL_DAYS')).toBe('日');
      expect(t('LBL_HOURS')).toBe('時間');
      expect(t('LBL_MINUTES_BEFORE')).toBe('分前に通知');
    });
  });
});
