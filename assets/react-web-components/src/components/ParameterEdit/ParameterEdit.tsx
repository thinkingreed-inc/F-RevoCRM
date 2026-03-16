import React, { useState, useEffect, useCallback } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, Loader2 } from 'lucide-react';
import { ParameterEditForm } from './ParameterEditForm';
import { useParameterData } from './hooks/useParameterData';
import { ParameterEditProps, ParameterFormState } from './types';
import { TranslationProvider } from '../../contexts/TranslationContext';
import { useTranslation } from '@/hooks/useTranslation';

/**
 * ParameterEdit - システム変数編集ダイアログ
 *
 * WebComponentとして使用される。
 * 外部からisOpen/onOpenChangeを制御し、ダイアログ形式で編集を行う。
 */
// export const ParameterEdit: React.FC<ParameterEditProps> = ({
const ParameterEditInner: React.FC<ParameterEditProps> = ({
  recordId,
  isOpen = false,
  onSave,
  onCancel,
  onOpenChange,
}) => {
  // 翻訳フック
  const { t } = useTranslation();
  // API通信用フック
  const { data, loading, saving, error, fetchRecord, saveRecord, clearData, clearError } = useParameterData();

  // フォーム状態
  const [formState, setFormState] = useState<ParameterFormState>({
    value: '',
    secret: false,
    description: '',
    error: null,
  });

  /**
   * ダイアログが開かれたらレコードを取得
   */
  useEffect(() => {
    // 初回展開時のref警告とJSONパースエラー対策
    if (isOpen && recordId) {
      const id = parseInt(recordId, 10);
      if (!isNaN(id)) {
        clearError();
        fetchRecord(id).then((record) => {
          if (record) {
            setFormState({
              value: record.value ?? '',
              description: record.description ?? '',
              secret: record.secret === 1,
              error: null,
            });
          }
        });
      }
    }
  }, [isOpen, recordId, fetchRecord, clearError]);

  /**
   * ダイアログを閉じる
   */
  const handleClose = useCallback(() => {
    clearData();
    setFormState({ value: '', description: '', secret: false, error: null });
    onOpenChange?.(false);
    onCancel?.();
  }, [clearData, onOpenChange, onCancel]);

  /**
   * 値のバリデーション
   */
  const validateValue = useCallback((): boolean => {
    if (!data) return false;

    // シークレットがONのままで元の値が取得不可の場合、空でも許可
    if (data.secret === 1 && formState.secret && formState.value === '') {
      return true;
    }

    // 空値は許可（シークレットOFFでも空文字で保存可能）
    if (formState.value === '') {
      return true;
    }

    // 整数型のバリデーション（値がある場合のみ）
    if (data.type === 'integer') {
      const num = Number(formState.value);
      if (isNaN(num) || !Number.isInteger(num)) {
        setFormState((prev: ParameterFormState) => ({ ...prev, error: t('LBL_INTEGER_ERROR') }));
        return false;
      }
    }

    setFormState((prev: ParameterFormState) => ({ ...prev, error: null }));
    return true;
  }, [data, formState.value, formState.description, formState.secret]);

  /**
   * 保存処理
   */
  const handleSave = useCallback(async () => {
    if (!data || !validateValue()) return;

    const payload = {
      id: data.id,
      value: formState.value,
      description: formState.description,
      secret: formState.secret ? 1 : 0,
    };

    const result = await saveRecord(payload);

    if (result.success) {
      onSave?.({ id: data.id, key: data.key, value: formState.value, description: formState.description });
      clearData();
      setFormState({ value: '', description: '',secret: false, error: null });
      onOpenChange?.(false);
    }
  }, [data, formState, validateValue, saveRecord, onSave, onOpenChange, clearData]);

  /**
   * 値変更ハンドラ
   */
  const handleValueChange = useCallback((value: string) => {
    setFormState((prev: ParameterFormState) => ({ ...prev, value, error: null }));
  }, []);

  /** 備考変更ハンドラ */
  const handleDescriptionChange = useCallback((description: string ) => {
    setFormState((prev: ParameterFormState) => ({ ...prev, description, error: null }));
  }, []);

  /**
   * シークレット変更ハンドラ
   */
  const handleSecretChange = useCallback((secret: boolean) => {
    setFormState((prev: ParameterFormState) => {
      // シークレットをOFFにした場合、値をクリアして新規入力を促す
      if (!secret && data?.secret === 1) {
        return { ...prev, secret, value: '' };
      }
      return { ...prev, secret };
    });
  }, [data]);

  /**
   * ダイアログ開閉ハンドラ
   */
  const handleOpenChange = useCallback((open: boolean) => {
    if (!open) {
      handleClose();
      return;
    }
    onOpenChange?.(open);
  }, [handleClose, onOpenChange]);

  return (
    <Dialog open={isOpen} onOpenChange={handleOpenChange}>
      <DialogContent
        className="sm:max-w-[600px] p-0 gap-0 overflow-hidden rounded-md border border-[#d6d6d6] shadow-md"
        closeButtonClassName="absolute top-[14px] right-[14px] text-white transition-all [&_svg]:size-[24px]"
      >
        <DialogHeader className="gap-0">
          <div className="bg-[#596875] text-white px-4 py-2">
            <DialogTitle asChild>
              <h4 className="text-white text-lg">{t('LBL_EDIT_MODULE')}</h4>
            </DialogTitle>
          </div>
          <DialogDescription className="sr-only">
            {t('LBL_PARAMETER_EDIT_DESCRIPTION')}
          </DialogDescription>
        </DialogHeader>

        <div className="px-5 pb-4">
          {/* ローディング */}
          {loading && (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
            </div>
          )}

          {/* エラー表示 */}
          {error && !loading && (
            <Alert className="mb-4" variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}

          {/* フォーム */}
          {data && !loading && (
            <ParameterEditForm
              record={data}
              value={formState.value}
              onValueChange={handleValueChange}
              description={formState.description}
              onDescriptionChange={handleDescriptionChange}
              secret={formState.secret}
              onSecretChange={handleSecretChange}
              error={formState.error}
              disabled={saving}
            />
          )}
        </div>

        <DialogFooter className="border-t px-5 py-3">
          <div className="flex flex-row justify-center gap-4 w-full">
            <Button
              onClick={handleSave}
              disabled={loading || saving || !data}
              className="bg-[#00A63E] text-white hover:bg-[#009136]"
            >
              {saving ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin text-white" />
                  {t('LBL_SAVING')}
                </>
              ) : (
                t('LBL_SAVE')
              )}
            </Button>
            <Button
              type="button"
              variant="link"
              onClick={handleClose}
              disabled={saving}
              className="px-2.5 py-1.5 h-auto text-md !text-red-600 hover:!text-red-800 no-underline hover:no-underline bg-transparent hover:bg-transparent border-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
              >
              {t('LBL_CANCEL')}
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export const ParameterEdit: React.FC<ParameterEditProps> = (props) => {
  if (!props.isOpen) {
    return null; // ダイアログが開いていない場合は何もレンダリングしない
  }
  return (
    <TranslationProvider module="Parameters" parent="Settings">
      <ParameterEditInner {...props} />
    </TranslationProvider>
  );
};