/**
 * TranslationContext - 翻訳データを管理するReact Context
 *
 * GetTranslations APIから取得した翻訳データを保持し、
 * アプリケーション全体で利用可能にする。
 */

import {
  createContext,
  useContext,
  useState,
  useCallback,
  useEffect,
  useMemo,
  ReactNode,
} from 'react';
import {
  fetchTranslations,
  mergeTranslations,
  TranslationData,
} from '../utils/translations';

/**
 * デフォルトの翻訳データ（フォールバック用）
 * 翻訳が取得できない場合に使用
 */
const DEFAULT_TRANSLATIONS: TranslationData = {
  // 基本操作
  LBL_SAVE: '保存',
  LBL_CANCEL: 'キャンセル',
  LBL_EDIT: '編集',
  LBL_DELETE: '削除',
  LBL_LOADING: '読み込み中',

  // QuickCreate
  LBL_QUICK_CREATE: 'クイック作成',
  LBL_GO_TO_FULL_FORM: '詳細入力',
  LBL_BASIC_INFORMATION: '基本情報',
  LBL_SAVING: '保存中...',
  LBL_UPDATING: '更新中...',
  LBL_UPDATE: '更新',

  // バリデーション
  LBL_FIELD_REQUIRED: '%sは必須です',
  LBL_FIELD_MAX_LENGTH: '%sは%s文字以内で入力してください',
  LBL_END_DATE_AFTER_START: '終了日時は開始日時より後に設定してください',

  // 成功メッセージ
  LBL_CREATED_SUCCESS: '%sを作成しました',
  LBL_UPDATED_SUCCESS: '%sを更新しました',

  // 状態
  LBL_LOADING_FIELDS: 'フィールド情報を読み込み中...',
  LBL_NO_FIELDS_AVAILABLE: '表示できるフィールドがありません',

  // カレンダー
  LBL_TASK: 'ToDo',
  LBL_EVENT: '活動',
  LBL_ALL_DAY: '終日',
  LBL_SET_REMINDER: '設定する',
  LBL_SEND_NOTIFICATION: '事前にメールを送信',
  LBL_START: '開始',
  LBL_DAYS: '日',
  LBL_HOURS: '時間',
  LBL_MINUTES_BEFORE: '分前に通知',
  LBL_INVITEES: '招待者',
  LBL_SELECT_INVITEES: '招待者を選択',
  LBL_INVITEES_SELECTED: '%s名選択中',
  LBL_CLEAR_ALL: 'すべてクリア',
  LBL_SEARCH_USERS_PLACEHOLDER: 'ユーザーを検索して追加...',
  LBL_ALL_USERS_SELECTED: 'すべてのユーザーが選択済みです',
  LBL_NO_MATCHING_USERS: '該当するユーザーがいません',

  // プレースホルダー
  LBL_PLACEHOLDER_ENTER: '%sを入力してください',
  LBL_PLACEHOLDER_SEARCH: '%sを検索...',
  LBL_PLACEHOLDER_SEARCH_AND_ADD: '%sを検索して追加...',
  LBL_PLACEHOLDER_SEARCH_TITLE: '%sを検索',
};

interface TranslationContextValue {
  /** 翻訳データ */
  translations: TranslationData;
  /** 現在の言語 */
  language: string;
  /** 翻訳データを読み込み中かどうか */
  isLoading: boolean;
  /** エラー情報 */
  error: Error | null;
  /** 翻訳データを再取得 */
  refetch: () => Promise<void>;
  /**
   * 翻訳関数
   * @param key - 翻訳キー
   * @param args - プレースホルダーに代入する値
   * @returns 翻訳された文字列
   */
  t: (key: string, ...args: (string | number)[]) => string;
}

const TranslationContext = createContext<TranslationContextValue | null>(null);

interface TranslationProviderProps {
  children: ReactNode;
  /** 対象モジュール名 */
  module: string;
  /** 初期翻訳データ（SSRやテスト用） */
  initialTranslations?: TranslationData;
  /** 言語コード（指定しない場合はサーバー側で決定） */
  language?: string;
}

/**
 * 翻訳プロバイダー
 *
 * 子コンポーネントに翻訳機能を提供する。
 * マウント時にGetTranslations APIから翻訳データを取得。
 * Vtiger標準のモジュール中心設計に準拠。
 */
export function TranslationProvider({
  children,
  module,
  initialTranslations,
  language: initialLanguage,
}: TranslationProviderProps) {
  const [translations, setTranslations] = useState<TranslationData>(
    initialTranslations || DEFAULT_TRANSLATIONS
  );
  const [language, setLanguage] = useState<string>(initialLanguage || 'ja_jp');
  const [isLoading, setIsLoading] = useState(!initialTranslations);
  const [error, setError] = useState<Error | null>(null);

  const loadTranslations = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);

      const response = await fetchTranslations({
        module,
        language: initialLanguage,
      });

      const merged = mergeTranslations(response);
      setTranslations({ ...DEFAULT_TRANSLATIONS, ...merged });
      setLanguage(response.language);
    } catch (err) {
      console.error('Failed to load translations:', err);
      setError(err instanceof Error ? err : new Error('Unknown error'));
      // エラー時はデフォルト翻訳を使用
      setTranslations(DEFAULT_TRANSLATIONS);
    } finally {
      setIsLoading(false);
    }
  }, [module, initialLanguage]);

  useEffect(() => {
    if (!initialTranslations) {
      loadTranslations();
    }
  }, [loadTranslations, initialTranslations]);

  /**
   * 翻訳関数
   * %s プレースホルダーを引数で置換
   * 正規表現を使用して安全に置換（置換値に%sが含まれていても問題なし）
   */
  const t = useCallback(
    (key: string, ...args: (string | number)[]): string => {
      const translated = translations[key] || key;

      if (args.length === 0) {
        return translated;
      }

      // 全ての%sを一度に抽出し、インデックスで置換
      let argIndex = 0;
      return translated.replace(/%s/g, () => {
        if (argIndex < args.length) {
          return String(args[argIndex++]);
        }
        return '%s'; // 引数が足りない場合はそのまま
      });
    },
    [translations]
  );

  const value = useMemo<TranslationContextValue>(
    () => ({
      translations,
      language,
      isLoading,
      error,
      refetch: loadTranslations,
      t,
    }),
    [translations, language, isLoading, error, loadTranslations, t]
  );

  return (
    <TranslationContext.Provider value={value}>
      {children}
    </TranslationContext.Provider>
  );
}

/**
 * 翻訳コンテキストを使用するフック
 *
 * @throws TranslationProvider外で呼び出された場合
 */
export function useTranslationContext(): TranslationContextValue {
  const context = useContext(TranslationContext);
  if (!context) {
    throw new Error(
      'useTranslationContext must be used within a TranslationProvider'
    );
  }
  return context;
}

/**
 * オプショナル翻訳コンテキストを使用するフック
 * TranslationProvider外で呼び出された場合はデフォルト翻訳を返す（エラーを投げない）
 */
export function useOptionalTranslationContext(): TranslationContextValue {
  const context = useContext(TranslationContext);
  if (!context) {
    // デフォルトの翻訳関数を返す
    const defaultT = (key: string, ...args: (string | number)[]): string => {
      const translated = DEFAULT_TRANSLATIONS[key] || key;
      if (args.length === 0) return translated;
      let argIndex = 0;
      return translated.replace(/%s/g, () => {
        if (argIndex < args.length) return String(args[argIndex++]);
        return '%s';
      });
    };
    return {
      translations: DEFAULT_TRANSLATIONS,
      language: 'ja_jp',
      isLoading: false,
      error: null,
      refetch: async () => {},
      t: defaultT,
    };
  }
  return context;
}

/**
 * テスト用：モック翻訳データでラップ
 */
export function MockTranslationProvider({
  children,
  translations = DEFAULT_TRANSLATIONS,
}: {
  children: ReactNode;
  translations?: TranslationData;
}) {
  return (
    <TranslationProvider initialTranslations={translations} module="Vtiger">
      {children}
    </TranslationProvider>
  );
}

export { DEFAULT_TRANSLATIONS };
