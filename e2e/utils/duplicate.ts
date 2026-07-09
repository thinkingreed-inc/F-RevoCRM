import { type Page } from "@playwright/test";
import { gotoDetail } from "./listview";
import { generateRandomString } from "./util";

/**
 * 詳細画面からレコードを複製し、保存後の新 record ID を返す。
 *
 * 複製導線は詳細画面の「その他」ドロップダウン内の複製リンクで、
 * `#<Module>_detailView_moreAction_LBL_DUPLICATE a` が EditView へ
 * `isDuplicate=true` 付きで遷移する(実機の Accounts 詳細で確認済み。
 * basicAction ではなく moreAction、ラベルトークンは LBL_DUPLICATE のまま)。
 * 遷移後の EditView は元レコードの全項目(名前含む)が事前入力された状態。
 *
 * 【重複防止ルールの回避】この環境は Accounts の「重複登録の防止」設定で
 * 顧客企業名の完全一致チェックが有効になっている。複製フォームは名前も
 * 元レコードと同一値で事前入力されるため、そのまま保存すると
 * 「重複が見つかりました」で保存がブロックされ、複製自体が作成できない
 * (これは重複防止機能としては正しい挙動であり、複製機能側のバグではない)。
 * そのため名前フィールド(vtiger 共通の `.nameField` クラスを持つ入力)の末尾に
 * ランダムサフィックスを付与し、完全一致を外してから保存する。元の名前は
 * 接頭辞としてそのまま残るため、元名での列検索(vtiger の列検索は contains
 * 判定)には複製後レコードも引き続きヒットする(一覧に元レコードと合わせて
 * 2件表示される)。
 *
 * 【既知バグ・非検証】複製元に添付ファイルがあっても複製先には引き継がれない
 * (長谷川さん確認済み・対応チケット化予定の既知バグ)。本ヘルパはレコード複製
 * そのものの成立のみを担い、添付ファイルの引継ぎは検証しない。
 */
export async function duplicateViaDetail(
  page: Page,
  module: string,
  recordId: string,
  app = "MARKETING"
): Promise<string> {
  await gotoDetail(page, module, recordId, app);
  await page
    .locator(".detailViewButtoncontainer button.dropdown-toggle")
    .first()
    .click();
  await page
    .locator(`#${module}_detailView_moreAction_LBL_DUPLICATE a`)
    .click();
  await page.waitForLoadState("domcontentloaded");

  // 重複防止ルールの完全一致チェックを外すため、名前フィールドにサフィックスを付与する。
  const nameInput = page.locator("input.nameField").first();
  const original = await nameInput.inputValue();
  await nameInput.fill(`${original}_${generateRandomString(4)}`);

  await page.locator("button.saveButton").first().click();
  await page.waitForURL(/[?&]record=\d+/, { timeout: 15000 });
  const newId = page.url().match(/record=(\d+)/)?.[1];
  if (!newId) throw new Error(`${module}: 複製の保存に失敗`);
  if (newId === recordId) throw new Error(`${module}: 複製で同一 ID(複製失敗)`);
  return newId;
}
