import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { DocumentsRelatedList } from "../DocumentsRelatedList";
import { clearTranslationCache } from "../../../utils/translations";

/**
 * 関連リストからの紐づけ解除（TS-13 ドキュメント関連付け）
 *
 * 紐づけたドキュメントを外す手段が無いと、間違った紐づけを直せない。
 * 解除ボタンが出ること・確認をキャンセルしたら何もしないこと・
 * 解除できなかったときに黙って消さないことを担保する。
 */

const UNLINK_JA = "紐づけを解除";
const UNLINK_CONFIRM_JA =
  "「%s」の紐づけを解除します。ドキュメント自体は削除されません。よろしいですか？";
const UNLINK_FAILED_JA = "紐づけを解除できませんでした";
const UNLINK_DENIED_JA =
  "参照できないフォルダのドキュメントのため、紐づけを解除できませんでした";
const COMPLIANCE_NOTE_JA =
  "このドキュメントは電子帳簿保存法の対象です。取引レコードとの紐づけが無くなると不適合になります。";

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

/** 一覧に出すドキュメント（電帳法の情報を持たせるかを切り替える） */
function makeRecord(withCompliance: boolean) {
  return {
    id: 100,
    title: "契約書.pdf",
    filename: "契約書.pdf",
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
    note_no: "DOC1",
    download_url: "index.php?module=Documents&action=DownloadFile&record=100",
    compliance: withCompliance
      ? {
          document_category: "contract",
          preservation_type: "scanner",
          compliance_status: "compliant",
          compliance_notes: null,
          input_deadline: "2026-08-25",
          input_deadline_status: "warning",
        }
      : null,
  };
}

/** RelationAPI の応答（テストごとに差し替える） */
let relationResponse: unknown = {
  success: true,
  result: { unlinked: 1, denied: 0 },
};
let listRecords: unknown[] = [];

function fetchImpl(url: string, init?: RequestInit): Promise<Response> {
  const u = String(url);
  if (u.includes("api=GetTranslations")) {
    return Promise.resolve(
      jsonResponse({
        module: "Documents",
        language: "ja_jp",
        translations: {
          Documents: {
            LBL_UNLINK_DOCUMENT: UNLINK_JA,
            LBL_UNLINK_CONFIRM: UNLINK_CONFIRM_JA,
            LBL_UNLINK_FAILED: UNLINK_FAILED_JA,
            LBL_UNLINK_DENIED: UNLINK_DENIED_JA,
            LBL_UNLINK_COMPLIANCE_NOTE: COMPLIANCE_NOTE_JA,
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
        result: { folders: [], totalCount: 0, starredCount: 0 },
      }),
    );
  }
  const body = init?.body;
  const params = new URLSearchParams(typeof body === "string" ? body : "");
  if (params.get("api") === "RelationAPI") {
    return Promise.resolve(jsonResponse(relationResponse));
  }
  if (params.get("api") === "ListAPI") {
    return Promise.resolve(
      jsonResponse({
        success: true,
        result: { records: listRecords, total: listRecords.length },
      }),
    );
  }
  return Promise.resolve(jsonResponse({ success: true, result: {} }));
}

/** RelationAPI に送られたリクエストのボディ */
function relationCalls(): URLSearchParams[] {
  return mockFetch.mock.calls
    .map(([, init]) => (init as RequestInit | undefined)?.body)
    .filter((body): body is string => typeof body === "string")
    .map((body) => new URLSearchParams(body))
    .filter((params) => params.get("api") === "RelationAPI");
}

async function renderList() {
  render(
    <DocumentsRelatedList parentModule="ServiceContracts" parentId={42} />,
  );
  // 一覧の描画（＝翻訳とレコード取得）を待つ
  return screen.findByTitle(UNLINK_JA);
}

describe("DocumentsRelatedList - 紐づけ解除", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    clearTranslationCache();
    listRecords = [makeRecord(false)];
    relationResponse = { success: true, result: { unlinked: 1, denied: 0 } };
    mockFetch.mockImplementation(fetchImpl);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("行に紐づけ解除の操作が出る", async () => {
    const button = await renderList();
    expect(button).toBeInTheDocument();
  });

  it("確認をキャンセルすると解除しない", async () => {
    const confirmSpy = vi.spyOn(window, "confirm").mockReturnValue(false);
    const button = await renderList();

    fireEvent.click(button);

    expect(confirmSpy).toHaveBeenCalled();
    expect(relationCalls()).toHaveLength(0);
  });

  it("確認するとRelationAPIのunlinkを親レコード指定で呼ぶ", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(true);
    const button = await renderList();

    fireEvent.click(button);

    await waitFor(() => expect(relationCalls()).toHaveLength(1));
    const params = relationCalls()[0];
    expect(params.get("mode")).toBe("unlink");
    expect(params.get("parent_module")).toBe("ServiceContracts");
    expect(params.get("parent_id")).toBe("42");
    expect(params.getAll("records[]")).toEqual(["100"]);
  });

  it("電帳法対象は不適合になり得ることを確認時に伝える", async () => {
    listRecords = [makeRecord(true)];
    const confirmSpy = vi.spyOn(window, "confirm").mockReturnValue(false);
    const button = await renderList();

    fireEvent.click(button);

    expect(confirmSpy.mock.calls[0][0]).toContain(COMPLIANCE_NOTE_JA);
  });

  it("電帳法対象でなければ不適合の注意は出さない", async () => {
    const confirmSpy = vi.spyOn(window, "confirm").mockReturnValue(false);
    const button = await renderList();

    fireEvent.click(button);

    expect(confirmSpy.mock.calls[0][0]).not.toContain(COMPLIANCE_NOTE_JA);
  });

  it("解除できなかったときは理由を知らせる", async () => {
    relationResponse = { success: true, result: { unlinked: 0, denied: 1 } };
    vi.spyOn(window, "confirm").mockReturnValue(true);
    const alertSpy = vi.spyOn(window, "alert").mockImplementation(() => {});
    const button = await renderList();

    fireEvent.click(button);

    await waitFor(() =>
      expect(alertSpy).toHaveBeenCalledWith(UNLINK_DENIED_JA),
    );
  });
});
