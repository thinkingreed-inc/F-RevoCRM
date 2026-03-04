/**
 * TranslationContext のテスト
 */

import { render, screen, waitFor, act } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import {
  TranslationProvider,
  useTranslationContext,
  MockTranslationProvider,
  DEFAULT_TRANSLATIONS,
} from '../TranslationContext';

// fetchTranslations のモック
vi.mock('../../utils/translations', () => ({
  fetchTranslations: vi.fn(),
  mergeTranslations: vi.fn((response) => response.translations?.Vtiger || {}),
}));

import { fetchTranslations } from '../../utils/translations';

// コンテキストを使用するテストコンポーネント
function TestConsumer() {
  const { t, isLoading, language, error } = useTranslationContext();

  if (isLoading) {
    return <div data-testid="loading">Loading...</div>;
  }

  if (error) {
    return <div data-testid="error">{error.message}</div>;
  }

  return (
    <div>
      <div data-testid="language">{language}</div>
      <div data-testid="translated">{t('LBL_SAVE')}</div>
      <div data-testid="with-placeholder">{t('LBL_FIELD_REQUIRED', 'タイトル')}</div>
      <div data-testid="unknown-key">{t('UNKNOWN_KEY')}</div>
    </div>
  );
}

describe('TranslationContext', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  describe('TranslationProvider', () => {
    it('初期状態でローディング中を表示', () => {
      vi.mocked(fetchTranslations).mockReturnValue(new Promise(() => {})); // 永続的にpending

      render(
        <TranslationProvider module="Vtiger">
          <TestConsumer />
        </TranslationProvider>
      );

      expect(screen.getByTestId('loading')).toBeInTheDocument();
    });

    it('API成功時に翻訳データを表示', async () => {
      vi.mocked(fetchTranslations).mockResolvedValue({
        success: true,
        module: 'Vtiger',
        language: 'ja_jp',
        translations: {
          Vtiger: {
            LBL_SAVE: '保存する',
            LBL_FIELD_REQUIRED: '%sは必須項目です',
          },
        },
      });

      render(
        <TranslationProvider module="Vtiger">
          <TestConsumer />
        </TranslationProvider>
      );

      await waitFor(() => {
        expect(screen.queryByTestId('loading')).not.toBeInTheDocument();
      });

      expect(screen.getByTestId('language')).toHaveTextContent('ja_jp');
      expect(screen.getByTestId('translated')).toHaveTextContent('保存する');
    });

    it('APIエラー時にエラー状態を設定し、デフォルト翻訳を使用', async () => {
      vi.mocked(fetchTranslations).mockRejectedValue(new Error('API Error'));

      // エラー時でもデフォルト翻訳が使用されることを確認するために、
      // errorを表示しないテストコンポーネントを使用
      function ErrorTestConsumer() {
        const { t, isLoading, error } = useTranslationContext();
        if (isLoading) return <div data-testid="loading">Loading...</div>;
        return (
          <div>
            <div data-testid="error-status">{error ? 'has-error' : 'no-error'}</div>
            <div data-testid="translated">{t('LBL_SAVE')}</div>
          </div>
        );
      }

      render(
        <TranslationProvider module="Vtiger">
          <ErrorTestConsumer />
        </TranslationProvider>
      );

      await waitFor(() => {
        expect(screen.queryByTestId('loading')).not.toBeInTheDocument();
      });

      // エラー状態が設定されている
      expect(screen.getByTestId('error-status')).toHaveTextContent('has-error');
      // デフォルト翻訳が使用される
      expect(screen.getByTestId('translated')).toHaveTextContent(DEFAULT_TRANSLATIONS.LBL_SAVE);
    });

    it('initialTranslations が提供された場合はAPI呼び出ししない', () => {
      const customTranslations = {
        LBL_SAVE: 'カスタム保存',
        LBL_FIELD_REQUIRED: '%sは必須',
      };

      render(
        <TranslationProvider initialTranslations={customTranslations} module="Vtiger">
          <TestConsumer />
        </TranslationProvider>
      );

      expect(fetchTranslations).not.toHaveBeenCalled();
      expect(screen.getByTestId('translated')).toHaveTextContent('カスタム保存');
    });

    it('language プロパティを設定可能', async () => {
      vi.mocked(fetchTranslations).mockResolvedValue({
        success: true,
        module: 'Vtiger',
        language: 'en_us',
        translations: {
          Vtiger: {
            LBL_SAVE: 'Save',
          },
        },
      });

      render(
        <TranslationProvider module="Vtiger" language="en_us">
          <TestConsumer />
        </TranslationProvider>
      );

      await waitFor(() => {
        expect(screen.getByTestId('language')).toHaveTextContent('en_us');
      });
    });

    it('module プロパティでAPIリクエストを制御', async () => {
      vi.mocked(fetchTranslations).mockResolvedValue({
        success: true,
        module: 'Potentials',
        language: 'ja_jp',
        translations: { Vtiger: {}, Potentials: {} },
      });

      render(
        <TranslationProvider module="Potentials">
          <TestConsumer />
        </TranslationProvider>
      );

      await waitFor(() => {
        expect(fetchTranslations).toHaveBeenCalledWith({
          module: 'Potentials',
          language: undefined,
        });
      });
    });
  });

  describe('useTranslationContext', () => {
    it('Provider 外で使用するとエラー', () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

      expect(() => {
        render(<TestConsumer />);
      }).toThrow('useTranslationContext must be used within a TranslationProvider');

      consoleError.mockRestore();
    });
  });

  describe('t 関数（翻訳関数）', () => {
    it('存在するキーの翻訳を返す', () => {
      render(
        <MockTranslationProvider translations={{ LBL_SAVE: 'テスト保存' }}>
          <TestConsumer />
        </MockTranslationProvider>
      );

      expect(screen.getByTestId('translated')).toHaveTextContent('テスト保存');
    });

    it('存在しないキーはキーそのものを返す', () => {
      render(
        <MockTranslationProvider>
          <TestConsumer />
        </MockTranslationProvider>
      );

      expect(screen.getByTestId('unknown-key')).toHaveTextContent('UNKNOWN_KEY');
    });

    it('%s プレースホルダーを置換', () => {
      render(
        <MockTranslationProvider translations={{ LBL_FIELD_REQUIRED: '%sは必須です' }}>
          <TestConsumer />
        </MockTranslationProvider>
      );

      expect(screen.getByTestId('with-placeholder')).toHaveTextContent('タイトルは必須です');
    });

    it('複数の %s プレースホルダーを順番に置換', () => {
      function MultiPlaceholderConsumer() {
        const { t } = useTranslationContext();
        return (
          <div data-testid="result">
            {t('LBL_FIELD_MAX_LENGTH', 'タイトル', '100')}
          </div>
        );
      }

      render(
        <MockTranslationProvider
          translations={{ LBL_FIELD_MAX_LENGTH: '%sは%s文字以内で入力してください' }}
        >
          <MultiPlaceholderConsumer />
        </MockTranslationProvider>
      );

      expect(screen.getByTestId('result')).toHaveTextContent(
        'タイトルは100文字以内で入力してください'
      );
    });

    it('プレースホルダーに数値を渡せる', () => {
      function NumberPlaceholderConsumer() {
        const { t } = useTranslationContext();
        return (
          <div data-testid="result">{t('LBL_INVITEES_SELECTED', 5)}</div>
        );
      }

      render(
        <MockTranslationProvider translations={{ LBL_INVITEES_SELECTED: '%s名選択中' }}>
          <NumberPlaceholderConsumer />
        </MockTranslationProvider>
      );

      expect(screen.getByTestId('result')).toHaveTextContent('5名選択中');
    });
  });

  describe('MockTranslationProvider', () => {
    it('デフォルト翻訳を提供', () => {
      render(
        <MockTranslationProvider>
          <TestConsumer />
        </MockTranslationProvider>
      );

      expect(screen.getByTestId('translated')).toHaveTextContent(DEFAULT_TRANSLATIONS.LBL_SAVE);
    });

    it('カスタム翻訳を上書き可能', () => {
      render(
        <MockTranslationProvider translations={{ LBL_SAVE: '保存ボタン' }}>
          <TestConsumer />
        </MockTranslationProvider>
      );

      expect(screen.getByTestId('translated')).toHaveTextContent('保存ボタン');
    });
  });

  describe('refetch 機能', () => {
    it('refetch で翻訳データを再取得', async () => {
      let callCount = 0;
      vi.mocked(fetchTranslations).mockImplementation(() => {
        callCount++;
        return Promise.resolve({
          success: true,
          module: 'Vtiger',
          language: 'ja_jp',
          translations: {
            Vtiger: {
              LBL_SAVE: callCount === 1 ? '初回' : '再取得',
            },
          },
        });
      });

      function RefetchConsumer() {
        const { t, refetch, isLoading } = useTranslationContext();
        return (
          <div>
            <div data-testid="value">{t('LBL_SAVE')}</div>
            <div data-testid="loading">{isLoading ? 'loading' : 'ready'}</div>
            <button onClick={() => refetch()} data-testid="refetch">
              Refetch
            </button>
          </div>
        );
      }

      render(
        <TranslationProvider module="Vtiger">
          <RefetchConsumer />
        </TranslationProvider>
      );

      await waitFor(() => {
        expect(screen.getByTestId('loading')).toHaveTextContent('ready');
      });

      expect(screen.getByTestId('value')).toHaveTextContent('初回');

      // refetch を実行
      await act(async () => {
        screen.getByTestId('refetch').click();
      });

      await waitFor(() => {
        expect(screen.getByTestId('value')).toHaveTextContent('再取得');
      });

      expect(fetchTranslations).toHaveBeenCalledTimes(2);
    });
  });

  describe('DEFAULT_TRANSLATIONS', () => {
    it('必要な翻訳キーが含まれている', () => {
      // 基本操作
      expect(DEFAULT_TRANSLATIONS.LBL_SAVE).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_CANCEL).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_EDIT).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_DELETE).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_LOADING).toBeDefined();

      // QuickCreate
      expect(DEFAULT_TRANSLATIONS.LBL_QUICK_CREATE).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_GO_TO_FULL_FORM).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_BASIC_INFORMATION).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_SAVING).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_UPDATE).toBeDefined();

      // バリデーション
      expect(DEFAULT_TRANSLATIONS.LBL_FIELD_REQUIRED).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_FIELD_MAX_LENGTH).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_END_DATE_AFTER_START).toBeDefined();

      // カレンダー
      expect(DEFAULT_TRANSLATIONS.LBL_TASK).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_EVENT).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_ALL_DAY).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_SET_REMINDER).toBeDefined();
      expect(DEFAULT_TRANSLATIONS.LBL_INVITEES).toBeDefined();
    });
  });
});
