import { TranslationProvider } from '@/contexts/TranslationContext';
import Tiptap, { TiptapProps } from './tiptap';

/**
 * TranslationProvider でラップした Tiptap WebComponent 用ラッパー
 *
 * <rich-text-editor> WebComponent として登録する際に使用。
 * module="Vtiger" を指定して Tiptap ツールバーの翻訳キーを提供する。
 *
 * TranslationProvider が必要な理由:
 *   createWebComponent() は Shadow DOM を使わず通常の DOM にマウントするため、
 *   QuickCreate 等の外側の TranslationProvider コンテキストは届かない。
 *   各 <rich-text-editor> に独自の TranslationProvider を持たせることで
 *   useOptionalTranslation() が GetTranslations API から翻訳を取得できるようになる。
 */
export const TiptapWithTranslation = (props: TiptapProps) => (
  <TranslationProvider module="Vtiger">
    <Tiptap {...props} />
  </TranslationProvider>
);
