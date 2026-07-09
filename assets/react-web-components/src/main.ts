import { createWebComponent } from "@/utils/createWebComponent";
import "./index.css";
import { QuickCreate, CalendarQuickCreate } from "@/components/QuickCreate";
import { AppMenu } from "@/components/AppMenu";
import { ActivityList } from "@/components/ActivityList";
import { CurlTaskForm } from "@/components/CurlTask";

// QuickCreate本体コンポーネントの登録
// イベント: save, cancel, go-to-full-form, open-change (CustomEvent)
// variant: "default" (一般モジュール) または "calendar" (Calendar/Events)
// record-id: 編集モード時のレコードID
//
// 使用例（HTML）:
// <quick-create module="Accounts" is-open="true"></quick-create>
//
// 使用例（JavaScript）:
// const qc = document.querySelector('quick-create');
// qc.setAttribute('module', 'Accounts');
// qc.setAttribute('is-open', 'true');
// qc.addEventListener('save', (e) => console.log('Saved:', e.detail));
// qc.addEventListener('cancel', () => console.log('Cancelled'));
createWebComponent(
  QuickCreate,
  "quick-create",
  ["module", "is-open", "initial-data", "variant", "record-id"],
  ["onSave", "onCancel", "onGoToFullForm", "onOpenChange"],
);

// Calendar/Events用QuickCreateコンポーネントの登録
// ※ CalendarQuickCreate は QuickCreate に統合されました（後方互換性のためエイリアスとしてエクスポート）
// module="Calendar" または module="Events" で自動的にカレンダーモードになります
// variant="calendar" で明示的にカレンダーモードを指定することも可能
// record-id: 編集モード時のレコードID
// is-duplicate: 複製モード時にtrue（recordIdが存在しても新規作成として扱う）
createWebComponent(
  CalendarQuickCreate,
  "calendar-quick-create",
  ["module", "is-open", "initial-data", "record-id", "is-duplicate"],
  ["onSave", "onCancel", "onGoToFullForm", "onOpenChange"],
);

// AppMenu コンポーネントの登録（Headerのアプリメニュー部分）
// app-menus属性: JSON形式のアプリメニューデータ
// 使用例（HTML）:
// <app-menu app-menus='[{"name":"MARKETING","label":"マーケティング","modules":[...]}]'></app-menu>
createWebComponent(AppMenu, "app-menu", ["app-menus"]);

// ActivityList コンポーネントの登録
// 親レコードに関連する活動一覧を表示
// mode: "upcoming" (今後), "overdue" (期限切れ), "all" (すべて)
createWebComponent(ActivityList, "activity-list", [
  "module",
  "record-id",
  "mode",
  "limit",
  "refresh-key",
]);

// Curlワークフロータスクの設定UI
// 属性: url, method, headers, body, timeout, fields-json, record-id, source-module
// fields-json: 差し込み可能フィールド一覧 [{"name":"subject","label":"件名"}, ...]
createWebComponent(CurlTaskForm, "vt-curl-task", [
  "url",
  "method",
  "headers",
  "body",
  "timeout",
  "fields-json",
  "labels-json",
  "record-id",
  "source-module",
]);
