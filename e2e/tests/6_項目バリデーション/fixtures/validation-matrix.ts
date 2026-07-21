// Excel「2_〇_OSS版_項目.xlsx」シート〇_項目テストの転記(単一の真実)。
// 入力値は原本どおり。期待種別は列見出しの規則(計画 Task 4 参照)。
export type FieldKey =
  | "str5" | "str255" | "decimal" | "integer" | "percent" | "currency"
  | "date" | "email" | "phone" | "picklist" | "url" | "checkbox"
  | "text" | "multipick" | "time" | "reference";

export type Expectation =
  | { kind: "acceptAsIs" }
  | { kind: "acceptNormalized"; stored: string }
  | { kind: "truncate"; maxLen: number }
  | { kind: "rejectWithError" }
  | { kind: "storedAsPlainText" }
  | { kind: "notRendered" };

export interface ValidationCase {
  field: FieldKey;
  scenario: string;
  input: string;
  expect: Expectation;
  note?: string;
}

export const FIELD_LABELS: Record<FieldKey, string> = {
  str5: "検証_単数行5",
  str255: "検証_単数行255",
  decimal: "検証_小数点",
  integer: "検証_整数",
  percent: "検証_パーセント",
  currency: "検証_通貨",
  date: "検証_日付",
  email: "検証_メール",
  phone: "検証_電話番号",
  picklist: "検証_選択肢単数",
  url: "検証_URL",
  checkbox: "検証_チェック",
  text: "検証_複数行",
  multipick: "検証_選択肢複数",
  time: "検証_時刻",
  reference: "検証_関連",
};

export const FIELD_GROUP: Record<string, FieldKey[]> = {
  単数行: ["str5", "str255"],
  数値: ["decimal", "integer", "percent", "currency"],
  日付時刻: ["date", "time"],
  書式: ["email", "phone", "url"],
  選択: ["picklist", "multipick", "checkbox"],
  関連: ["reference"],
};

const SQLI = "; DROP TABLE users; --";
const SCRIPTI = "<script>alert('XSS')</script>";
const LONG255 =
  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456!@#$%^&*()_+-=[]{}|;:',.<>?/" +
  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789" +
  "あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをん" +
  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567"; // 255文字
const MULTILINE = "これは複数行のテストです。\n改行を含みます。\n123";

/**
 * Excel 期待と実 CRM 挙動が乖離すると判明したケース(調査済み・別PR対応予定)。
 * ここに載ったケースはスペックで test.fixme として保留し、CI を green に保つ。
 */
export const KNOWN_DIVERGENCES: { field: FieldKey; scenario: string; reason: string }[] = [
  {
    field: "str255",
    scenario: "前後スペース",
    reason:
      "実挙動: 保存時に前後の空白を自動トリムして正常保存する(Excel期待=エラーで保存拒否)。" +
      "record=841/846 で確認: char_length(cf_871)=255、先頭バイトは半角空白(0x20)ではなく'A'(0x41) = 先頭スペースは除去された上でLONG255がそのまま保存されている。要CRM調査/別PR。",
  },
  {
    field: "integer",
    scenario: "長さ超過",
    reason:
      "実挙動: 桁数超過(11桁 '12345678901')でもエラー拒否されずMySQL int型の上限に丸めて正常保存する" +
      "(Excel期待=エラーで保存拒否)。record=892 で確認: vtiger_faqcf.cf_873(int型)=2147483647(INT32上限)。" +
      "バリデーションが桁数ではなくPHP/DB側のint丸めに依存しているため。要CRM調査/別PR。",
  },
  {
    field: "date",
    scenario: "その他無効",
    reason:
      "実挙動: 存在しない日付(2026-02-30)でもエラー拒否されず'0000-00-00'として正常保存する" +
      "(Excel期待=エラーで保存拒否)。record=915 で確認: vtiger_faqcf.cf_876(date型)='0000-00-00'。" +
      "CRM側で日付の実在性チェックをしておらずMySQL date型がそのまま無効値をゼロ日付に丸めているため。要CRM調査/別PR。",
  },
  {
    field: "time",
    scenario: "マイナス",
    reason:
      "実挙動: 負の時刻(-10:00)がエラー拒否されず正常保存する(Excel期待=エラーで保存拒否)。" +
      "record=914 で確認: vtiger_faqcf.cf_884(time型)='-10:00:00'。MySQLのTIME型は" +
      "時刻ではなく期間を表現できるため-838:59:59〜838:59:59の負値を許容し、CRM側も" +
      "時刻としての妥当性チェックをしていないため。要CRM調査/別PR。",
  },
];

export const CASES: ValidationCase[] = [
  // --- 単数行5 ---
  { field: "str5", scenario: "有効", input: "あいうえお", expect: { kind: "acceptAsIs" } },
  { field: "str5", scenario: "長さ超過", input: "あいうえおか", expect: { kind: "truncate", maxLen: 5 } },
  { field: "str5", scenario: "前後スペース", input: " あいう　", expect: { kind: "acceptAsIs" } },

  // --- 単数行255 ---
  { field: "str255", scenario: "有効", input: LONG255, expect: { kind: "acceptAsIs" } },
  { field: "str255", scenario: "長さ超過", input: LONG255 + "あ", expect: { kind: "truncate", maxLen: 255 } },
  { field: "str255", scenario: "前後スペース", input: " " + LONG255, expect: { kind: "rejectWithError" }, note: "255は前後スペース除外(切り詰め/エラー)" },
  { field: "str255", scenario: "SQLi", input: SQLI, expect: { kind: "storedAsPlainText" } },
  { field: "str255", scenario: "スクリプトi", input: SCRIPTI, expect: { kind: "notRendered" } },

  // --- 小数点 ---
  { field: "decimal", scenario: "有効", input: "1234567.89", expect: { kind: "acceptAsIs" } },
  { field: "decimal", scenario: "マイナス", input: "-1234567.89", expect: { kind: "acceptAsIs" } },
  { field: "decimal", scenario: "最小", input: "0.01", expect: { kind: "acceptAsIs" } },
  { field: "decimal", scenario: "最大", input: "999999.99", expect: { kind: "acceptAsIs" } },
  { field: "decimal", scenario: "全角数字", input: "１２３", expect: { kind: "rejectWithError" } },
  { field: "decimal", scenario: "かな", input: "1.あ", expect: { kind: "rejectWithError" } },
  { field: "decimal", scenario: "英字", input: "1.a", expect: { kind: "rejectWithError" } },
  { field: "decimal", scenario: "ゼロ", input: "0", expect: { kind: "acceptAsIs" } },

  // --- 整数 ---
  { field: "integer", scenario: "有効", input: "1234567890", expect: { kind: "acceptAsIs" } },
  { field: "integer", scenario: "マイナス", input: "-1234567890", expect: { kind: "acceptAsIs" } },
  { field: "integer", scenario: "最小", input: "1", expect: { kind: "acceptAsIs" } },
  { field: "integer", scenario: "最大", input: "999999999", expect: { kind: "acceptAsIs" } },
  { field: "integer", scenario: "長さ超過", input: "12345678901", expect: { kind: "rejectWithError" } },
  { field: "integer", scenario: "全角数字", input: "１２３４５", expect: { kind: "rejectWithError" } },
  { field: "integer", scenario: "かな", input: "1あ", expect: { kind: "rejectWithError" } },
  { field: "integer", scenario: "英字", input: "1a", expect: { kind: "rejectWithError" } },
  { field: "integer", scenario: "ゼロ", input: "0", expect: { kind: "acceptAsIs" } },

  // --- パーセント ---
  { field: "percent", scenario: "有効", input: "99", expect: { kind: "acceptAsIs" } },
  { field: "percent", scenario: "マイナス", input: "-55", expect: { kind: "acceptAsIs" } },
  { field: "percent", scenario: "最小", input: "0", expect: { kind: "acceptAsIs" } },
  { field: "percent", scenario: "最大", input: "1000", expect: { kind: "acceptNormalized", stored: "999.99" }, note: "NUMERIC(5,2)上限" },
  { field: "percent", scenario: "全角数字", input: "１２３", expect: { kind: "rejectWithError" } },
  { field: "percent", scenario: "かな", input: "9あ", expect: { kind: "rejectWithError" } },
  { field: "percent", scenario: "英字", input: "1a", expect: { kind: "rejectWithError" } },
  { field: "percent", scenario: "ゼロ", input: "0", expect: { kind: "acceptAsIs" } },

  // --- 通貨 ---
  { field: "currency", scenario: "有効", input: "123456789", expect: { kind: "acceptAsIs" } },
  { field: "currency", scenario: "マイナス", input: "-555", expect: { kind: "acceptAsIs" } },
  { field: "currency", scenario: "最小", input: "1", expect: { kind: "acceptAsIs" } },
  { field: "currency", scenario: "全角数字", input: "１２３４５", expect: { kind: "rejectWithError" } },
  { field: "currency", scenario: "かな", input: "1234あいう", expect: { kind: "rejectWithError" } },
  { field: "currency", scenario: "英字", input: "1235abc", expect: { kind: "rejectWithError" } },
  { field: "currency", scenario: "ゼロ", input: "0", expect: { kind: "acceptAsIs" } },

  // --- 日付 --- (入力はUI形式 yyyy-mm-dd)
  { field: "date", scenario: "有効", input: "2026-02-28", expect: { kind: "acceptAsIs" } },
  { field: "date", scenario: "かな", input: "2026/2/あ", expect: { kind: "rejectWithError" } },
  { field: "date", scenario: "英字", input: "2026/2/a", expect: { kind: "rejectWithError" } },
  { field: "date", scenario: "その他無効", input: "2026-02-30", expect: { kind: "rejectWithError" }, note: "存在しない日" },

  // --- メール ---
  { field: "email", scenario: "有効", input: "example@test.com", expect: { kind: "acceptAsIs" } },
  { field: "email", scenario: "全角数字", input: "example１２３@test.com", expect: { kind: "rejectWithError" } },
  { field: "email", scenario: "かな", input: "exampleあ@test.com", expect: { kind: "rejectWithError" } },
  { field: "email", scenario: "その他無効", input: "invalid@com", expect: { kind: "rejectWithError" } },

  // --- 電話番号 --- (全角/かな/英字も許容=acceptAsIs)
  { field: "phone", scenario: "有効", input: "090-1234-5678", expect: { kind: "acceptAsIs" } },
  { field: "phone", scenario: "かな", input: "090-1234-あいうえ", expect: { kind: "acceptAsIs" }, note: "電話は許容" },
  { field: "phone", scenario: "英字", input: "090-1234-abcd", expect: { kind: "acceptAsIs" }, note: "電話は許容" },

  // --- 選択肢単数 ---
  { field: "picklist", scenario: "有効", input: "B", expect: { kind: "acceptAsIs" } },

  // --- URL ---
  { field: "url", scenario: "有効", input: "http://google.com", expect: { kind: "acceptAsIs" } },
  { field: "url", scenario: "全角数字", input: "http://google１２３.com", expect: { kind: "rejectWithError" } },
  { field: "url", scenario: "かな", input: "http://googleあいう.com", expect: { kind: "rejectWithError" } },
  { field: "url", scenario: "その他無効", input: "invalidcom", expect: { kind: "rejectWithError" }, note: ".を含む英数字+ハイフンのみ許容" },

  // --- チェックボックス ---
  { field: "checkbox", scenario: "有効", input: "true", expect: { kind: "acceptAsIs" } },

  // --- テキスト複数行 ---
  { field: "text", scenario: "有効", input: MULTILINE, expect: { kind: "acceptAsIs" } },
  { field: "text", scenario: "SQLi", input: SQLI, expect: { kind: "storedAsPlainText" } },
  { field: "text", scenario: "スクリプトi", input: SCRIPTI, expect: { kind: "notRendered" } },

  // --- 選択肢複数 ---
  { field: "multipick", scenario: "有効", input: "A,B,C,D", expect: { kind: "acceptAsIs" } },

  // --- 時刻 --- (入力はUI形式 HH:mm)
  { field: "time", scenario: "有効", input: "14:30", expect: { kind: "acceptAsIs" } },
  { field: "time", scenario: "マイナス", input: "-10:00", expect: { kind: "rejectWithError" } },
  { field: "time", scenario: "かな", input: "10:0あ", expect: { kind: "rejectWithError" } },
  { field: "time", scenario: "英字", input: "10:0a", expect: { kind: "rejectWithError" } },
  { field: "time", scenario: "その他無効", input: "25:61", expect: { kind: "rejectWithError" } },

  // --- 関連モジュール --- (input はダミー。参照は先頭Accountsレコードを選択)
  { field: "reference", scenario: "有効", input: "", expect: { kind: "acceptAsIs" }, note: "先頭Accountsを選択" },
];
