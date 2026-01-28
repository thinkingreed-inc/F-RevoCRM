import React, { useMemo } from 'react';
import { FieldRenderer } from '../FieldRenderer';
import { QuickCreateFormProps } from '../../types/quickcreate';
import { FieldInfo, FieldValue } from '../../types/field';
import { cn } from '../../lib/utils';
import { useTranslation } from '../../hooks/useTranslation';

/**
 * QuickCreateForm - QuickCreate用フォーム表示コンポーネント
 * FieldRendererを使用して各フィールドをレンダリング
 */
export const QuickCreateForm: React.FC<QuickCreateFormProps> = ({
  module: _module,
  fields,
  formData,
  onFieldChange,
  onRecordTypeChange,
  isSaving = false,
  disabled = false,
  errors = {}
}) => {
  const { t } = useTranslation();

  // _moduleは将来的にモジュール固有の処理で使用予定
  void _module;
  /**
   * フィールドをブロック別にグループ化
   */
  const fieldsByBlock = useMemo(() => {
    const blocks: Record<string, FieldInfo[]> = {};

    fields.forEach(field => {
      const blockLabel = (field.fieldinfo?.block as string | undefined) || t('LBL_BASIC_INFORMATION');
      if (!blocks[blockLabel]) {
        blocks[blockLabel] = [];
      }
      blocks[blockLabel].push(field);
    });

    return blocks;
  }, [fields, t]);

  /**
   * フィールド値変更ハンドラ
   */
  const handleFieldChange = (fieldName: string, value: FieldValue) => {
    if (!disabled && !isSaving) {
      onFieldChange(fieldName, value);
    }
  };

  if (fields.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        {t('LBL_NO_FIELDS_AVAILABLE')}
      </div>
    );
  }

  return (
    <div className="quickcreate-form space-y-3 text-md">
      {Object.entries(fieldsByBlock).map(([blockName, blockFields]) => (
        <div key={blockName} className="quickcreate-block">
            <h4 className="fieldBlockHeader font-bold leading-[1.1] mt-0 mb-2 pb-1 border-b border-gray-300">
              {blockName}
            </h4>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 pr-8">
            {blockFields.map(field => (
              <div
                key={field.name}
                className={cn(
                  'quickcreate-field',
                  // TextAreaは2カラム幅
                  (field.uitype === '19' || field.uitype === '21') && 'md:col-span-2'
                )}
              >
                <FieldRenderer
                  field={field}
                  value={formData[field.name]}
                  onChange={handleFieldChange}
                  onRecordTypeChange={onRecordTypeChange}
                  disabled={disabled || isSaving}
                  error={errors[field.name]}
                  className="w-full"
                  formData={formData}
                />
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
};

export default QuickCreateForm;
