import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { DocumentsPage } from "../DocumentsPage";
import { clearTranslationCache } from "../../../utils/translations";

/**
 * 一覧の絞り込みを変えたときの選択解除（TS-14 一括操作）
 *
 * 選択したまま絞り込みを変えると、画面に出ていないドキュメントを一括操作して
 * しまう。フォルダ・検索と同じく、電帳法フィルターの変更でも選択を解除する。
 * 逆にページ送りでは解除しない（ページをまたいで選択できるようにしている）。
 *
 * 選択中の表示（「n件を選択中」のバー）はヘッダーの実寸を測ってから出すため
 * jsdom では描画されない。そのためチェックボックスの状態で確認する。
 */

const COMPLIANCE_FILTER_JA = "電帳法";
const ALL_CATEGORIES_JA = "全書類区分";
const ALL_STATUSES_JA = "すべての適合状態";
const ALL_RECORDS_JA = "全レコード";
const ALL_DEADLINE_JA = "すべての期限状態";

const mockFetch = vi.fn();
global.fetch = mockFetch as unknown as typeof fetch;

function jsonResponse(body: unknown) {
  const text = JSON.stringify(body);
  return {
    ok: true,
    status: 200,
    statusText: "OK",
    text: async () => text,
    json: async () => JSON.parse(text),
  } as unknown as Response;
}

/** 2ページ分になる件数（ページ送りの挙動も確認するため） */
const TOTAL = 25;

function makeRecords() {
  return [1, 2].map((id) => ({
    id,
    title: `ドキュメント${id}`,
    filename: `doc${id}.pdf`,
    filetype: "application/pdf",
    filesize: 1024,
    filelocationtype: "I",
    folderid: 1,
    foldername: "Default",
    assigned_user_id: "1",
    assigned_user_name: "admin",
    modifiedtime: "2026-08-21 10:00:00",
    createdtime: "2026-08-21 10:00:00",
    filedownloadcount: 0,
    filestatus: 1,
    fileversion: null,
    starred: false,
    notecontent: null,
    note_no: `DOC${id}`,
    download_url: "",
    compliance: null,
  }));
}

function fetchImpl(url: string, init?: RequestInit): Promise<Response> {
  const u = String(url);
  if (u.includes("api=GetTranslations")) {
    return Promise.resolve(
      jsonResponse({
        module: "Documents",
        language: "ja_jp",
        translations: {
          Documents: {
            LBL_COMPLIANCE_FILTER_LABEL: COMPLIANCE_FILTER_JA,
            LBL_ALL_CATEGORIES: ALL_CATEGORIES_JA,
            LBL_ALL_STATUSES: ALL_STATUSES_JA,
            LBL_ALL_RECORDS: ALL_RECORDS_JA,
            LBL_ALL_DEADLINE_STATUSES: ALL_DEADLINE_JA,
          },
        },
        timestamp: "",
      }),
    );
  }
  if (u.includes("api=FolderAPI")) {
    return Promise.resolve(
      jsonResponse({
        success: true,
        result: { folders: [], totalCount: TOTAL, starredCount: 0 },
      }),
    );
  }
  const body = init?.body;
  const params = new URLSearchParams(typeof body === "string" ? body : "");
  if (params.get("api") === "ListAPI") {
    return Promise.resolve(
      jsonResponse({
        success: true,
        result: { records: makeRecords(), total: TOTAL },
      }),
    );
  }
  return Promise.resolve(jsonResponse({ success: true, result: {} }));
}

/** 1行目のチェックボックス（行の aria-label はドキュメントのタイトル） */
function firstRowCheckbox(): HTMLInputElement {
  return screen.getByLabelText("ドキュメント1") as HTMLInputElement;
}

/** 一覧を出して1件選択する */
async function renderAndSelectOne() {
  render(<DocumentsPage />);
  const checkbox = (await screen.findByLabelText(
    "ドキュメント1",
  )) as HTMLInputElement;
  fireEvent.click(checkbox);
  expect(checkbox.checked).toBe(true);
}

/** 電帳法フィルターをONにする（セレクトはONのときだけ出る） */
async function enableComplianceFilter() {
  fireEvent.click(screen.getByLabelText(COMPLIANCE_FILTER_JA));
  await screen.findByDisplayValue(ALL_CATEGORIES_JA);
}

/** 選択し直す（フィルターONで一度解除されるため） */
async function selectAgain() {
  await waitFor(() => expect(firstRowCheckbox().checked).toBe(false));
  fireEvent.click(firstRowCheckbox());
  expect(firstRowCheckbox().checked).toBe(true);
}

describe("DocumentsPage - 絞り込み変更時の選択解除", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    clearTranslationCache();
    localStorage.clear();
    mockFetch.mockImplementation(fetchImpl);
    window.history.replaceState(null, "", "/index.php?module=Documents");
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("電帳法フィルターのON/OFFで選択が解除される", async () => {
    await renderAndSelectOne();

    await enableComplianceFilter();

    await waitFor(() => expect(firstRowCheckbox().checked).toBe(false));
  });

  it("書類区分を変えると選択が解除される", async () => {
    await renderAndSelectOne();
    await enableComplianceFilter();
    await selectAgain();

    fireEvent.change(screen.getByDisplayValue(ALL_CATEGORIES_JA), {
      target: { value: "receipt" },
    });

    await waitFor(() => expect(firstRowCheckbox().checked).toBe(false));
  });

  it("適合状態を変えると選択が解除される", async () => {
    await renderAndSelectOne();
    await enableComplianceFilter();
    await selectAgain();

    fireEvent.change(screen.getByDisplayValue(ALL_STATUSES_JA), {
      target: { value: "non_compliant" },
    });

    await waitFor(() => expect(firstRowCheckbox().checked).toBe(false));
  });

  it("未関連のみに変えると選択が解除される", async () => {
    await renderAndSelectOne();
    await enableComplianceFilter();
    await selectAgain();

    fireEvent.change(screen.getByDisplayValue(ALL_RECORDS_JA), {
      target: { value: "false" },
    });

    await waitFor(() => expect(firstRowCheckbox().checked).toBe(false));
  });

  it("期限状態を変えると選択が解除される", async () => {
    await renderAndSelectOne();
    await enableComplianceFilter();
    await selectAgain();

    fireEvent.change(screen.getByDisplayValue(ALL_DEADLINE_JA), {
      target: { value: "overdue" },
    });

    await waitFor(() => expect(firstRowCheckbox().checked).toBe(false));
  });

  it("ページ送りでは選択を解除しない（ページをまたいで選択できる）", async () => {
    await renderAndSelectOne();

    const nextButtons = screen.getAllByText(">");
    fireEvent.click(nextButtons[nextButtons.length - 1]);

    await waitFor(() =>
      expect(
        mockFetch.mock.calls.some(([, init]) => {
          const body = (init as RequestInit | undefined)?.body;
          return (
            typeof body === "string" &&
            new URLSearchParams(body).get("page") === "2"
          );
        }),
      ).toBe(true),
    );
    expect(firstRowCheckbox().checked).toBe(true);
  });
});
