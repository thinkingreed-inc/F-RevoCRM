import { expect, Page, Locator } from "@playwright/test";
import { url } from "./util";

/**
 * 在庫系(Invoice/Quotes/SalesOrder/PurchaseOrder)の明細(LineItems)ドライバ。
 *
 * 4 モジュールは実装を共有する(layouts/v7/modules/Inventory/partials/LineItems*.tpl,
 * public/layouts/v7/modules/Inventory/resources/Edit.js)。そのため DOM/JS も共通で、
 * ドライバは 1 本でよく、モジュール名でパラメータ化する。
 *
 * セレクタは LineItemsEdit.tpl / LineItemsContent.tpl / Edit.js から確定:
 *  - 行:            tr#row{N}.lineItemRow(新規作成時は #row1 が事前描画される)
 *  - 商品名(補完):  #productName{N}.autoComplete  → 選択で #hdnProductId{N} と #listPrice{N} が充填
 *  - 数量/単価:      #qty{N} / #listPrice{N}
 *  - 行割引:         .individualDiscount → Bootstrap popover(.discountForm)内で
 *                     input.discounts[data-discount-type] + input.discount_percentage/amount → .popoverButton 保存
 *  - 行の計算セル:   #productTotal{N} / #discountTotal{N} / #totalAfterDiscount{N} / #netPrice{N}
 *  - 合計:           #netTotal / #preTaxTotal / #tax_final / #grandTotal
 */

/** 通貨表記("2,000.00" 等)を数値へ。区切りカンマ・通貨記号を除去する。 */
export function parseNum(text: string | null): number {
  if (!text) return 0;
  const cleaned = text.replace(/[^0-9.\-]/g, "");
  const n = parseFloat(cleaned);
  return Number.isFinite(n) ? n : 0;
}

/** DOM テキスト/入力値を数値で読む。 */
async function numOf(page: Page, selector: string): Promise<number> {
  const loc = page.locator(selector).first();
  const tag = await loc.evaluate((el) => el.tagName.toLowerCase());
  const raw =
    tag === "input" || tag === "textarea"
      ? await loc.inputValue()
      : await loc.textContent();
  return parseNum(raw);
}

/** 在庫モジュールの新規作成(Edit)画面へ遷移し、明細テーブルの描画を待つ。 */
export async function gotoInventoryEdit(
  page: Page,
  moduleName: string,
  app = "MARKETING"
): Promise<void> {
  await page.goto(url(`index.php?module=${moduleName}&view=Edit&app=${app}`));
  await page.waitForLoadState("domcontentloaded");
  await expect(page.locator("#lineItemTab")).toBeVisible();
}

/** 在庫モジュールの既存レコードを編集(Edit)で開く。 */
export async function gotoInventoryEditRecord(
  page: Page,
  moduleName: string,
  recordId: string,
  app = "MARKETING"
): Promise<void> {
  await page.goto(
    url(
      `index.php?module=${moduleName}&view=Edit&record=${recordId}&app=${app}`
    )
  );
  await page.waitForLoadState("domcontentloaded");
  await expect(page.locator("#lineItemTab")).toBeVisible();
}

/**
 * 参照項目(account_id / vendor_id 等)を設定する。
 *
 * 参照のオートコンプリートは不安定なため、選択時にアプリが行うのと同じ
 * 「hidden(sourceField)=id / _display=表示名」を直接セットして確定する。
 * 対象は「テスト対象ではないヘッダ項目」なので、既知レコードを直接充てるのが堅牢。
 */
export async function setReference(
  page: Page,
  fieldName: string,
  recordId: string,
  displayName: string
): Promise<void> {
  await page.evaluate(
    ({ fieldName, recordId, displayName }) => {
      const hidden = document.querySelector<HTMLInputElement>(
        `input[name="${fieldName}"].sourceField`
      );
      const display = document.querySelector<HTMLInputElement>(
        `#${fieldName}_display`
      );
      if (hidden) {
        hidden.value = String(recordId);
        hidden.dispatchEvent(new Event("change", { bubbles: true }));
      }
      if (display) {
        display.removeAttribute("disabled");
        display.value = displayName;
        display.dispatchEvent(new Event("change", { bubbles: true }));
      }
    },
    { fieldName, recordId, displayName }
  );
}

/** 必須ピックリストを「オプションの選択(空)」でなく最初の実値にする。 */
async function setPicklistFirstValue(
  page: Page,
  name: string
): Promise<void> {
  await page.evaluate((name) => {
    const sel = document.querySelector<HTMLSelectElement>(
      `select[name="${name}"]`
    );
    if (!sel) return;
    for (const o of Array.from(sel.options)) {
      if (o.value && o.value !== "") {
        sel.value = o.value;
        sel.dispatchEvent(new Event("change", { bubbles: true }));
        return;
      }
    }
  }, name);
}

export interface InventoryHeader {
  subject: string;
  /** 参照項目(Invoice/Quotes/SalesOrder は account_id, PurchaseOrder は vendor_id)。 */
  reference: { field: string; recordId: string; displayName: string };
  /** 既定が「オプションの選択(空)」の必須ピックリスト(見積ステージ等)。最初の実値を選ぶ。 */
  picklists?: string[];
}

/** 在庫ヘッダの必須項目(件名 / 参照 / 請求先・納品先住所 / 必須ピックリスト)を埋める。 */
export async function fillInventoryHeader(
  page: Page,
  header: InventoryHeader
): Promise<void> {
  await page.fill('input[name="subject"]', header.subject);
  await setReference(
    page,
    header.reference.field,
    header.reference.recordId,
    header.reference.displayName
  );
  // 必須の住所(uitype 24 = textarea)。値の内容は問わないので固定文言で埋める。
  await page.fill('textarea[name="bill_street"]', "E2E 請求先住所");
  await page.fill('textarea[name="ship_street"]', "E2E 納品先住所");
  for (const name of header.picklists ?? []) {
    await setPicklistFirstValue(page, name);
  }
}

/**
 * 明細行に商品/サービスを設定する(既定は事前描画の #row1)。
 *
 * 品目名のオートコンプリート(input.autoComplete)は本体バグで使えない
 * (Products_Record_Model::getSearchResult の SELECT で label が
 *  vtiger_crmentity/vtiger_products の両方に存在し曖昧 → SQLエラー → 0件)。
 * そこで箱アイコン(.lineItemPopup)のダイアログを使う。こちらは
 * Vtiger_ListView_Model::getInstanceForPopup による ListView 検索で列が
 * 正しく修飾されるため動作する。ダイアログの行クリックで選択が確定し、
 * GetTaxes 経由で #hdnProductId / #listPrice が充填される。
 */
export async function fillProductLine(
  page: Page,
  opts: {
    searchKey: string;
    qty?: number;
    rowNo?: number;
    module?: "Products" | "Services";
    /** 明細の定価を明示設定する。PurchaseOrder は選択時に定価=仕入原価(0)となるため必須。 */
    listPrice?: number;
  }
): Promise<void> {
  const rowNo = opts.rowNo ?? 1;
  const module = opts.module ?? "Products";
  const searchField = module === "Services" ? "servicename" : "productname";

  // 明細行の 商品/サービス ポップアップ(箱アイコン)を開く。
  await page
    .locator(`#row${rowNo} .lineItemPopup[data-module-name="${module}"]`)
    .click();
  const popup = page.locator("#popupPageContainer");
  await expect(popup).toBeVisible();

  // 名前で絞り込み → 一致行をクリック(行クリックで選択確定しダイアログが閉じる)。
  await popup
    .locator(`input.listSearchContributor[name="${searchField}"]`)
    .fill(opts.searchKey);
  await popup.locator('[data-trigger="PopupListSearch"]').click();
  await popup
    .locator("tr.listViewEntries", { hasText: opts.searchKey })
    .first()
    .click();

  // 選択で GetTaxes が走り hidden の productid が充填される。
  await expect(page.locator(`#hdnProductId${rowNo}`)).toHaveValue(/\d+/, {
    timeout: 15000,
  });

  // 定価を明示設定(検算を決定論的にする。PO は自動設定が 0 のため必須)。
  if (opts.listPrice != null) {
    const lp = page.locator(`#listPrice${rowNo}`);
    await lp.fill(String(opts.listPrice));
    await lp.blur();
  }
  await expect(page.locator(`#listPrice${rowNo}`)).toHaveValue(/[1-9]/, {
    timeout: 15000,
  });

  if (opts.qty != null) {
    await setQty(page, rowNo, opts.qty);
  }

  // 合計が 0 でなくなる(=明細が有効に計上された)ことを待つ。
  await expect(page.locator("#grandTotal")).toHaveText(/[1-9]/, {
    timeout: 10000,
  });
}

/**
 * 行割引を設定する(Bootstrap popover 経由)。
 * type: 'percentage' は % 値、'amount' は金額。
 */
export async function setLineDiscount(
  page: Page,
  rowNo: number,
  opts: { type: "percentage" | "amount"; value: number }
): Promise<void> {
  await page.locator(`#row${rowNo} .individualDiscount`).click();
  const pop = page.locator(".popover.discountForm").last();
  await expect(pop).toBeVisible();

  await pop
    .locator(`input.discounts[data-discount-type="${opts.type}"]`)
    .check();
  const field =
    opts.type === "percentage"
      ? pop.locator("input.discount_percentage")
      : pop.locator("input.discount_amount");
  await field.fill(String(opts.value));

  await pop.locator(".popoverButton").click();
  await expect(pop).toBeHidden();
  // 割引適用後の再計算を待つ(割引額セルが 0 以外に更新される)。
  await expect(page.locator(`#discountTotal${rowNo}`)).toHaveText(/[1-9]/, {
    timeout: 10000,
  });
}

export interface InventoryTotals {
  netTotal: number;
  preTaxTotal: number;
  taxFinal: number;
  grandTotal: number;
}

/** 合計ブロック(明細下部)の主要値を数値で読む。 */
export async function readTotals(page: Page): Promise<InventoryTotals> {
  return {
    netTotal: await numOf(page, "#netTotal"),
    preTaxTotal: await numOf(page, "#preTaxTotal"),
    taxFinal: await numOf(page, "#tax_final"),
    grandTotal: await numOf(page, "#grandTotal"),
  };
}

/** 行の計算セル(#productTotal{N} 等)を数値で読む。 */
export async function readLineCell(
  page: Page,
  cell:
    | "productTotal"
    | "discountTotal"
    | "totalAfterDiscount"
    | "taxTotal"
    | "netPrice",
  rowNo = 1
): Promise<number> {
  return numOf(page, `#${cell}${rowNo}`);
}

/** 数量入力(再計算のため focusout する)。 */
export async function setQty(
  page: Page,
  rowNo: number,
  qty: number
): Promise<void> {
  const qtyInput = page.locator(`#qty${rowNo}`);
  await qtyInput.fill(String(qty));
  await qtyInput.blur();
}

/** 保存ボタン(明細フォーム)。 */
export function saveButton(page: Page): Locator {
  return page.locator("#EditView button.saveButton").first();
}
