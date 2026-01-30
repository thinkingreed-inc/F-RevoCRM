/**
 * React WebComponents エントリーポイント
 *
 * QuickCreate機能とAppMenuをWebComponentとして登録
 * ※ CSSはShadow DOM内で読み込むため、ここではimportしない
 */
import { createWebComponent } from "@/utils/createWebComponent";
import { QuickCreate, CalendarQuickCreate } from "@/components/QuickCreate";
import { AppMenu } from "@/components/AppMenu";

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
  ["onSave", "onCancel", "onGoToFullForm", "onOpenChange"]
);

// Calendar/Events用QuickCreateコンポーネントの登録
// ※ CalendarQuickCreate は QuickCreate に統合されました（後方互換性のためエイリアスとしてエクスポート）
// module="Calendar" または module="Events" で自動的にカレンダーモードになります
// variant="calendar" で明示的にカレンダーモードを指定することも可能
// record-id: 編集モード時のレコードID
createWebComponent(
  CalendarQuickCreate,
  "calendar-quick-create",
  ["module", "is-open", "initial-data", "record-id"],
  ["onSave", "onCancel", "onGoToFullForm", "onOpenChange"]
);

// AppMenu コンポーネントの登録（Headerのアプリメニュー部分）
// app-menus属性: JSON形式のアプリメニューデータ
// 使用例（HTML）:
// <app-menu app-menus='[{"name":"MARKETING","label":"マーケティング","modules":[...]}]'></app-menu>
createWebComponent(AppMenu, "app-menu", ["app-menus"]);
