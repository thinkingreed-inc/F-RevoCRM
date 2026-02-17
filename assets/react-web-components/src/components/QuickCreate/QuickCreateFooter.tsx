import React from 'react';
import { Button } from '../ui/button';
import { Loader2 } from 'lucide-react';
import { QuickCreateFooterProps } from '../../types/quickcreate';
import { useTranslation } from '../../hooks/useTranslation';

/**
 * QuickCreateFooter - QuickCreateモーダルのフッターボタン
 */
export const QuickCreateFooter: React.FC<QuickCreateFooterProps> = ({
  module: _module,
  onSave,
  onCancel,
  onGoToFullForm,
  isSaving = false,
  saveDisabled = false
}) => {
  const { t } = useTranslation();

  // _moduleは将来的にモジュール固有の処理で使用予定
  void _module;

  return (
    <div className="quickcreate-footer flex items-center justify-center gap-4 px-4 py-3 border-t border-gray-200">
      {/* 完全フォームへ遷移ボタン - F-RevoCRM本体の btn-default スタイル */}
      <Button
        type="button"
        variant="outline"
        onClick={onGoToFullForm}
        disabled={isSaving}
        className="px-6 py-1.5 h-auto text-md font-bold bg-white hover:bg-gray-100 border-gray-300"
      >
        {t('LBL_GO_TO_FULL_FORM')}
      </Button>

      {/* 保存ボタン - F-RevoCRM本体の btn-success スタイル */}
      <Button
        type="button"
        onClick={onSave}
        disabled={isSaving || saveDisabled}
        className="px-6 py-1.5 h-auto text-md font-bold !bg-green-600 hover:!bg-green-700 !text-white"
      >
        {isSaving ? (
          <>
            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            {t('LBL_SAVING')}
          </>
        ) : (
          t('LBL_SAVE')
        )}
      </Button>

      {/* キャンセルリンク - F-RevoCRM本体の cancelLink スタイル（リンク形式・赤文字） */}
      <Button
        type="button"
        variant="link"
        onClick={onCancel}
        disabled={isSaving}
        className="px-2.5 py-1.5 h-auto text-md !text-red-600 hover:!text-red-800 hover:no-underline"
      >
        {t('LBL_CANCEL')}
      </Button>
    </div>
  );
};

export default QuickCreateFooter;
