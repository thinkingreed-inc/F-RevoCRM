/**
 * useTranslation - 翻訳機能を提供するカスタムフック
 *
 * TranslationContextから翻訳関数を取得し、
 * コンポーネントで簡単に翻訳を使用できるようにする。
 */

import { useTranslationContext, useOptionalTranslationContext } from '../contexts/TranslationContext';

/**
 * 翻訳フック
 *
 * @returns t関数と関連ユーティリティ
 *
 * @example
 * ```tsx
 * const { t, isLoading } = useTranslation();
 *
 * if (isLoading) return <Loading />;
 *
 * return (
 *   <div>
 *     <h1>{t('LBL_QUICK_CREATE')}</h1>
 *     <p>{t('LBL_FIELD_REQUIRED', 'タイトル')}</p>
 *     <button>{t('LBL_SAVE')}</button>
 *   </div>
 * );
 * ```
 */
export function useTranslation() {
  const context = useTranslationContext();

  return {
    /**
     * 翻訳関数
     * @param key - 翻訳キー（例: 'LBL_SAVE'）
     * @param args - プレースホルダー置換用の引数
     * @returns 翻訳された文字列（キーが見つからない場合はキーそのまま）
     */
    t: context.t,

    /**
     * 翻訳データ読み込み中フラグ
     */
    isLoading: context.isLoading,

    /**
     * エラー情報
     */
    error: context.error,

    /**
     * 現在の言語コード（例: 'ja_jp', 'en_us'）
     */
    language: context.language,

    /**
     * 翻訳データを再取得
     */
    refetch: context.refetch,

    /**
     * 全翻訳データ（直接アクセスが必要な場合）
     */
    translations: context.translations,
  };
}

/**
 * オプショナル翻訳フック
 * TranslationProvider外で使用してもエラーを投げず、デフォルト翻訳を使用する
 *
 * @example
 * ```tsx
 * const { t } = useOptionalTranslation();
 * // TranslationProvider外でも安全に使用可能
 * return <p>{t('LBL_SAVE')}</p>;
 * ```
 */
export function useOptionalTranslation() {
  const context = useOptionalTranslationContext();

  return {
    t: context.t,
    isLoading: context.isLoading,
    error: context.error,
    language: context.language,
    refetch: context.refetch,
    translations: context.translations,
  };
}

export default useTranslation;
