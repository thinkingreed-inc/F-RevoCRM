import type { Page } from "@playwright/test";
import { test, expect } from "../../fixtures/isolated";
import { createRecordViaApi, deleteRecordViaApi } from "../../utils/record";
import { frQuery, frRetrieve } from "../../model/fetcher";
import { generateRandomString, url } from "../../utils/util";
import {
  convertSection,
  deleteConvertedRecords,
  ensureConvertPrerequisites,
  modeButton,
  openLeadConvert,
  submitConvert,
} from "../../utils/leadConvert";

/**
 * リード昇格画面（Web コンポーネント <lead-convert>）— ローカル確認用（CI の CI_SPECS には入れない）
 *
 * 昇格の起動と既定どおりの昇格は CI 対象の 4-3_リード.spec.ts が担保する。ここでは
 *  - 昇格済み判定に当たった既存企業が最初からセットされ、昇格しても既存企業の値は変わらない
 *  - 企業・担当者・案件をすべて新規作成する
 *  - 既存担当者への紐づけ（所属企業が空ならセット／別企業なら警告して変えない）
 *  - 画面側の検証エラー
 *  - 画面に出していない項目のサーバー側エラー → 項目を追加表示 → 修正 → 昇格
 * を検証する。
 *
 * テストデータはテストごとに接頭辞＋ランダムな名前で作り、後始末で消す。
 * 判定条件などの設定は CRM 全体で共有のため、前提（企業は会社名一致・担当者はメール一致・
 * 案件の営業ステージの昇格時デフォルト値）は ensureConvertPrerequisites が
 * 「足りなければ足す」だけにする（4-13_リード昇格設定 とはロックで排他）。
 * 画面に出していない項目のテストは、企業の表示項目に Web サイトが無い（既定）ことを前提にする。
 */
test.describe("リード昇格（Web コンポーネント）", () => {
  test.beforeEach(async ({ page }) => {
    // 設定 API は CRM の画面上で CSRF トークンを付けて呼ぶため、先に任意の画面を開く
    await page.goto(url("index.php?module=Leads&view=List"));
    await ensureConvertPrerequisites(page);
  });

  const accountWsIdOf = async (session: string, name: string) => {
    const rows = await frQuery(session, `SELECT id FROM Accounts WHERE accountname='${name}';`);
    return rows.map((row) => row.id);
  };

  const recordIdOf = (wsId: string) => wsId.split("x")[1];

  const wsIdOf = async (session: string, module: string, field: string, value: string) => {
    const rows = await frQuery(session, `SELECT id FROM ${module} WHERE ${field}='${value}';`);
    return rows[0]?.id ?? "";
  };

  const expectDialogStaysOpen = async (page: Page) => {
    await expect(page.getByTestId("lead-convert-dialog")).toBeVisible();
    expect(page.url()).toMatch(/[?&]module=Leads/);
  };

  test("会社名が一致する既存企業がセットされ、昇格しても既存企業の値は上書きされない", async ({ page }) => {
    const token = `LCE2E${generateRandomString(8)}`;
    const company = `${token} 既存商事`;
    const account = await createRecordViaApi("Accounts", {
      accountname: company,
      industry: "Banking",
      phone: "03-1111-2222",
    });
    const lead = await createRecordViaApi("Leads", {
      company,
      lastname: `${token}姓`,
      industry: "Apparel",
      phone: "06-3333-4444",
    });
    try {
      await openLeadConvert(page, lead.recordId);

      const accounts = convertSection(page, "Accounts");
      await expect(accounts.getByTestId("lead-convert-match-Accounts")).toBeVisible();
      await expect(modeButton(page, "Accounts", "既存のレコードに紐づける")).toBeChecked();
      // 紐づけ先として既存企業が入っている（概要にも会社名が出る）
      await expect(accounts.getByTestId("lead-convert-summary-Accounts")).toContainText(company);

      const recordId = await submitConvert(page, "Accounts");
      expect(recordId).toBe(account.recordId);

      const saved = await frRetrieve(account.session, account.wsId);
      expect(saved && saved.industry).toBe("Banking");
      expect(saved && saved.phone).toBe("03-1111-2222");
      // 新しく作った担当者は既存企業に紐づく
      const contactId = await wsIdOf(account.session, "Contacts", "lastname", `${token}姓`);
      const contact = await frRetrieve(account.session, contactId);
      expect(contact && contact.account_id).toBe(account.wsId);
      // 同じ会社名の企業は増えていない
      expect(await accountWsIdOf(account.session, company)).toEqual([account.wsId]);
    } finally {
      await deleteConvertedRecords(account.session, token);
      await deleteRecordViaApi(lead.session, lead.wsId);
    }
  });

  test("企業・担当者・案件をすべて新しく作成できる", async ({ page }) => {
    const token = `LCE2E${generateRandomString(8)}`;
    const company = `${token} 新規開拓`;
    const lead = await createRecordViaApi("Leads", {
      company,
      lastname: `${token}姓`,
      email: `${token.toLowerCase()}@example.com`,
    });
    try {
      await openLeadConvert(page, lead.recordId);
      await expect(modeButton(page, "Accounts", "新しく作成する")).toBeChecked();
      await expect(modeButton(page, "Contacts", "新しく作成する")).toBeChecked();

      await modeButton(page, "Potentials", "新しく作成する").click();
      const potentials = convertSection(page, "Potentials");
      await potentials.locator('input[name="potentialname"]').fill(`${token} 初回案件`);
      await potentials.locator('input[name="closingdate"]').fill("2030-12-25");

      const accountId = await submitConvert(page, "Accounts");

      const session = lead.session;
      const accountWsIds = await accountWsIdOf(session, company);
      expect(accountWsIds.map(recordIdOf)).toEqual([accountId]);
      const contact = await frRetrieve(session, await wsIdOf(session, "Contacts", "lastname", `${token}姓`));
      expect(contact && contact.account_id).toBe(accountWsIds[0]);
      const potential = await frRetrieve(session, await wsIdOf(session, "Potentials", "potentialname", `${token} 初回案件`));
      expect(potential && potential.related_to).toBe(accountWsIds[0]);
      expect(potential && potential.contact_id).toBe(contact && contact.id);
      expect(potential && potential.sales_stage).toBeTruthy();
    } finally {
      await deleteConvertedRecords(lead.session, token);
      await deleteRecordViaApi(lead.session, lead.wsId);
    }
  });

  test("所属企業が空の既存担当者に紐づけると、昇格で作った企業が所属にセットされる", async ({ page }) => {
    const token = `LCE2E${generateRandomString(8)}`;
    const email = `${token.toLowerCase()}@example.com`;
    const contact = await createRecordViaApi("Contacts", {
      lastname: `${token}既存担当`,
      email,
      phone: "045-000-1111",
    });
    const lead = await createRecordViaApi("Leads", {
      company: `${token} 所属なし企画`,
      lastname: `${token}姓`,
      email,
      phone: "045-999-9999",
    });
    try {
      await openLeadConvert(page, lead.recordId);

      const contacts = convertSection(page, "Contacts");
      await expect(contacts.getByTestId("lead-convert-match-Contacts")).toBeVisible();
      await expect(modeButton(page, "Contacts", "既存のレコードに紐づける")).toBeChecked();
      await expect(contacts.getByText("所属企業が未設定")).toBeVisible();

      const accountId = await submitConvert(page, "Accounts");

      const saved = await frRetrieve(contact.session, contact.wsId);
      expect(saved && recordIdOf(saved.account_id)).toBe(accountId);
      // 既存担当者のほかの値は変わらない
      expect(saved && saved.lastname).toBe(`${token}既存担当`);
      expect(saved && saved.phone).toBe("045-000-1111");
    } finally {
      await deleteConvertedRecords(contact.session, token);
      await deleteRecordViaApi(lead.session, lead.wsId);
    }
  });

  test("別の企業に所属する既存担当者に紐づけると警告が出て、所属は変わらない", async ({ page }) => {
    const token = `LCE2E${generateRandomString(8)}`;
    const email = `${token.toLowerCase()}@example.com`;
    const other = await createRecordViaApi("Accounts", { accountname: `${token} 所属先` });
    const contact = await createRecordViaApi("Contacts", {
      lastname: `${token}既存担当`,
      email,
      account_id: other.wsId,
    });
    const lead = await createRecordViaApi("Leads", {
      company: `${token} 別会社`,
      lastname: `${token}姓`,
      email,
    });
    try {
      await openLeadConvert(page, lead.recordId);

      const contacts = convertSection(page, "Contacts");
      await expect(modeButton(page, "Contacts", "既存のレコードに紐づける")).toBeChecked();
      await expect(contacts.getByText(`別の企業（${token} 所属先）に所属`)).toBeVisible();

      await submitConvert(page, "Accounts");

      const saved = await frRetrieve(contact.session, contact.wsId);
      expect(saved && saved.account_id).toBe(other.wsId);
    } finally {
      await deleteConvertedRecords(contact.session, token);
      await deleteRecordViaApi(lead.session, lead.wsId);
    }
  });

  test("必須項目を空にすると画面でエラーになり昇格しない", async ({ page }) => {
    const token = `LCE2E${generateRandomString(8)}`;
    const lead = await createRecordViaApi("Leads", {
      company: `${token} 検証`,
      lastname: `${token}姓`,
    });
    try {
      await openLeadConvert(page, lead.recordId);
      const contacts = convertSection(page, "Contacts");
      await contacts.locator('input[name="lastname"]').fill("");

      await page.getByTestId("lead-convert-submit").click();

      await expect(contacts.getByText(/必須/).first()).toBeVisible();
      await expectDialogStaysOpen(page);
      expect(await accountWsIdOf(lead.session, `${token} 検証`)).toEqual([]);
    } finally {
      await deleteConvertedRecords(lead.session, token);
      await deleteRecordViaApi(lead.session, lead.wsId);
    }
  });

  test("画面に出していない項目のサーバー側エラーは項目を追加表示し、直せば昇格できる", async ({ page }) => {
    const token = `LCE2E${generateRandomString(8)}`;
    const company = `${token} 非表示検証`;
    const lead = await createRecordViaApi("Leads", {
      company,
      lastname: `${token}姓`,
      website: "これはURLではない",
    });
    try {
      await openLeadConvert(page, lead.recordId);
      const accounts = convertSection(page, "Accounts");
      // 企業の Web サイトは表示項目に無い（リードの値はマッピングで入る）
      await expect(accounts.locator('input[name="website"]')).toHaveCount(0);

      await page.getByTestId("lead-convert-submit").click();

      await expect(page.getByTestId("lead-convert-errors")).toBeVisible();
      await expect(accounts.getByText("確認が必要なため追加表示した項目")).toBeVisible();
      const website = accounts.locator('input[name="website"]');
      await expect(website).toHaveValue("これはURLではない");
      await expectDialogStaysOpen(page);

      await website.fill("https://example.com/lead-convert");
      const accountId = await submitConvert(page, "Accounts");

      const accountWsIds = await accountWsIdOf(lead.session, company);
      expect(accountWsIds.map(recordIdOf)).toEqual([accountId]);
      const saved = await frRetrieve(lead.session, accountWsIds[0]);
      expect(saved && saved.website).toBe("https://example.com/lead-convert");
    } finally {
      await deleteConvertedRecords(lead.session, token);
      await deleteRecordViaApi(lead.session, lead.wsId);
    }
  });
});
