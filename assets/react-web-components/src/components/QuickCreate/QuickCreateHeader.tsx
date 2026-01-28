import React from 'react';
import { DialogTitle } from '../ui/dialog';
import { useTranslation } from '../../hooks/useTranslation';

/**
 * QuickCreateHeader のプロパティ
 */
export interface QuickCreateHeaderProps {
  /** モジュールの表示名 */
  moduleLabel: string;
  /** スタイルバリアント */
  variant?: 'default' | 'calendar';
  /** 編集モードかどうか */
  isEditMode?: boolean;
  /** カレンダーバリアント用の現在のタブ */
  activeTab?: 'Calendar' | 'Events';
}

/**
 * QuickCreateモーダルの統一ヘッダーコンポーネント
 *
 * 通常モジュール用のデフォルトバリアントと、
 * Calendar/Events用のカレンダーバリアントをサポート
 *
 * 両バリアントで同一のスタイル（bg-[#596875]背景）を使用し、
 * F-RevoCRM既存UIとの統一感を保つ
 *
 * @example
 * // デフォルトバリアント（通常モジュール）
 * <QuickCreateHeader
 *   moduleLabel="商談"
 *   variant="default"
 * />
 *
 * @example
 * // カレンダーバリアント
 * <QuickCreateHeader
 *   moduleLabel="Calendar"
 *   variant="calendar"
 *   isEditMode={false}
 *   activeTab="Calendar"
 * />
 */
export const QuickCreateHeader: React.FC<QuickCreateHeaderProps> = ({
  moduleLabel,
  variant = 'default',
  isEditMode = false,
  activeTab = 'Calendar',
}) => {
  const { t } = useTranslation();

  // タイトルテキストを生成
  // 既存のF-RevoCRMに合わせて: クイック作成 ToDo / クイック作成 活動
  const getTitleText = (): string => {
    if (variant === 'calendar') {
      const tabLabel = activeTab === 'Calendar' ? t('LBL_TASK') : t('LBL_EVENT');
      const modeLabel = isEditMode ? t('LBL_EDIT') : t('LBL_QUICK_CREATE');
      return `${modeLabel} ${tabLabel}`;
    }
    return `${t('LBL_QUICK_CREATE')} ${moduleLabel}`;
  };

  // 両バリアントで統一されたヘッダースタイル
  return (
    <div className="bg-[#596875] text-white px-4 py-1.5 rounded-t-lg">
      <DialogTitle asChild>
        <h4 className="text-white text-base">{getTitleText()}</h4>
      </DialogTitle>
    </div>
  );
};
