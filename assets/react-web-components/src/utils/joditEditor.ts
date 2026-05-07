import { FieldInfo } from '../types/field';

/**
 * QuickCreate上のJoditインスタンスからデータを収集してformDataに反映する。
 * submit前に呼び出すことで、Reactのstateに反映されていない最新エディタ内容を取得できる。
 *
 * @param module     モジュール名（エディタIDのプレフィックス）
 * @param formData   現在のフォームデータ
 * @param fields     フィールド定義（isJoditEditor===trueのものだけ処理）
 * @returns          Joditフィールドを上書きした新しいformDataオブジェクト
 */
export function syncJoditEditorFormData(
  module: string,
  formData: Record<string, unknown>,
  fields: FieldInfo[]
): Record<string, unknown> {
  const JoditEditor = (window as any).Vtiger_Jodit_Js;
  if (!JoditEditor || fields.length === 0) {
    return formData;
  }

  if (typeof JoditEditor.syncAllInstances === 'function') {
    JoditEditor.syncAllInstances();
  }

  const result = { ...formData };
  fields.forEach(field => {
    if (!field.isJoditEditor) {
      return;
    }

    const editorId = `${module || 'QuickCreate'}_quickCreate_fieldName_${field.name}`;
    const wrapper = typeof JoditEditor.getInstance === 'function'
      ? JoditEditor.getInstance(editorId)
      : null;

    if (wrapper && typeof wrapper.getData === 'function') {
      result[field.name] = wrapper.getData();
      return;
    }

    const textarea = document.getElementById(editorId) as HTMLTextAreaElement | null;
    if (textarea) {
      result[field.name] = textarea.value;
    }
  });

  return result;
}
