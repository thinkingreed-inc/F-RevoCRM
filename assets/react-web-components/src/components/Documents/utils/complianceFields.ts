/**
 * 電子帳簿保存法・スキャナ保存の入力欄の出し入れ
 *
 * 登録・編集画面では、選んだ内容に関係のない入力欄を出さない。
 *   - URL（filelocationtype = E）は電帳法に対応できないため、両ブロックを出さない
 *   - 書類区分を選ぶまでは、保存区分・受領日などの関連項目を出さない
 *   - スキャナ固有の項目（解像度・カラー区分など）は
 *     保存区分が「スキャナ保存」のときだけ出す
 */

/** 書類区分を選んだときに出す項目 */
export const CATEGORY_DEPENDENT_FIELDS = new Set([
  "preservation_type",
  "receipt_date",
]);

/** 保存区分が「スキャナ保存」のときだけ出す項目 */
export const SCANNER_ONLY_FIELDS = new Set([
  "scan_resolution_dpi",
  "scan_color_type",
  "original_paper_size",
  "scanned_by",
  "scanned_at",
]);

export interface ComplianceVisibilityParams {
  /** 判定する項目名 */
  fieldName: string;
  /** その項目が属するブロック名（翻訳済み） */
  blockLabel: string;
  /** ドキュメント種別。"I" = ファイル、"E" = URL */
  docType: string;
  /** 選択中の書類区分 */
  documentCategory: string;
  /** 選択中の保存区分 */
  preservationType: string;
  /** 「電子帳簿保存法」ブロックの表示名 */
  complianceBlockLabel: string;
  /** 「スキャナ保存」ブロックの表示名 */
  scannerBlockLabel: string;
}

/**
 * 入力欄を表示するかどうかを返す
 *
 * 電帳法・スキャナ保存以外のブロックの項目は常に表示する（判定の対象外）。
 */
export function isComplianceFieldVisible({
  fieldName,
  blockLabel,
  docType,
  documentCategory,
  preservationType,
  complianceBlockLabel,
  scannerBlockLabel,
}: ComplianceVisibilityParams): boolean {
  const isComplianceBlock =
    blockLabel === complianceBlockLabel || blockLabel === scannerBlockLabel;
  if (!isComplianceBlock) return true;

  // URL は電帳法の対象外なのでブロックごと出さない
  if (docType !== "I") return false;

  // 書類区分は入口なので常に出す
  if (fieldName === "document_category") return true;

  if (documentCategory === "") return false;
  if (CATEGORY_DEPENDENT_FIELDS.has(fieldName)) return true;
  if (SCANNER_ONLY_FIELDS.has(fieldName)) {
    return preservationType === "scanner";
  }
  // 上記以外（管理者が電帳法ブロックに追加した項目など）は書類区分を選べば出す
  return true;
}
