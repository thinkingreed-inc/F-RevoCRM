/**
 * QuickCreate コンポーネント群
 */

// メインコンポーネント
export { QuickCreate } from './QuickCreate';
export { default } from './QuickCreate';

// 後方互換性: CalendarQuickCreate は QuickCreate に統合されました
// Calendar/Events モジュールは自動的にカレンダーモードで表示されます
export { QuickCreate as CalendarQuickCreate } from './QuickCreate';

// サブコンポーネント
export { QuickCreateForm } from './QuickCreateForm';
export { QuickCreateFooter } from './QuickCreateFooter';
export { QuickCreateHeader } from './QuickCreateHeader';
export { CalendarForm } from './CalendarForm';
export type { CalendarFormProps } from './CalendarForm';

// Hooks
export { useQuickCreateFields } from './hooks/useQuickCreateFields';
export type { UseQuickCreateFieldsResultExtended } from './hooks/useQuickCreateFields';
export { useQuickCreateSave } from './hooks/useQuickCreateSave';
export { usePicklistDependency } from './hooks/usePicklistDependency';
export type { UsePicklistDependencyResult } from './hooks/usePicklistDependency';
export { useCalendarFields } from './hooks/useCalendarFields';
export type { UseCalendarFieldsParams, UseCalendarFieldsResult } from './hooks/useCalendarFields';
export { useRecordData } from './hooks/useRecordData';
export type { UseRecordDataParams, UseRecordDataResult } from './hooks/useRecordData';
