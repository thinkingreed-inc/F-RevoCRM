import { describe, it, expect } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { ComplianceHistoryModal } from "../ComplianceHistoryModal";
import { TranslationProvider } from "../../../contexts/TranslationContext";
import type { AuditLogEntry } from "../types/documents";

/**
 * 変更履歴の表示（TS-05 / TS-10）
 *
 * ModTracker と監査ログを統合した履歴を出す。詳細列にファイルハッシュのような
 * 長い文字列が入るため、列幅を固定しないと操作者が1文字ずつ折り返される。
 */

const TRANSLATIONS: Record<string, string> = {
  LBL_AUDIT_LOG: "変更履歴",
  LBL_FILE_VERSIONS: "ファイルバージョン",
  LBL_COL_DATETIME: "日時",
  LBL_COL_ACTION: "操作",
  LBL_COL_PERFORMER: "操作者",
  LBL_COL_DETAIL: "詳細",
  LBL_ACTION_CREATE: "登録",
  LBL_ACTION_UPDATE: "更新",
  LBL_ACTION_DELETE: "削除",
  LBL_ACTION_RESTORE: "復元",
  LBL_ACTION_DOWNLOAD: "ダウンロード",
  LBL_HISTORY_SUMMARY: "%s: %s件 / %s: %s件",
};

const PERFORMER = "システム管理者";

/** ModTracker 由来の履歴（復元は ModTracker にしか無い） */
function makeEntries(): AuditLogEntry[] {
  return [
    {
      entry_id: 2820,
      source: "modtracker",
      action_type: "restore",
      action_detail: null,
      file_hash_before: null,
      file_hash_after: null,
      performed_by: 1,
      performer_name: PERFORMER,
      performed_at: "2026-08-21 17:26:28",
      ip_address: null,
    },
    {
      entry_id: 2819,
      source: "modtracker",
      action_type: "delete",
      action_detail: null,
      file_hash_before: null,
      file_hash_after: null,
      performed_by: 1,
      performer_name: PERFORMER,
      performed_at: "2026-08-21 17:26:13",
      ip_address: null,
    },
    {
      // 記録元が違えばIDは重複し得る（キーは source と組で作る）
      entry_id: 2820,
      source: "audit",
      action_type: "download",
      action_detail: null,
      file_hash_before: null,
      file_hash_after: null,
      performed_by: 1,
      performer_name: PERFORMER,
      performed_at: "2026-08-20 10:11:12",
      ip_address: "192.168.0.1",
    },
    {
      entry_id: 815,
      source: "audit",
      action_type: "update",
      action_detail: {
        changes: [
          {
            field: "file_hash",
            label: "ファイルハッシュ",
            old_value: null,
            new_value:
              "7725cec5b2b85aef83a51ce7af3ac96775ef62718242e59822bb5474a563a3fb",
          },
        ],
      },
      file_hash_before: null,
      file_hash_after:
        "7725cec5b2b85aef83a51ce7af3ac96775ef62718242e59822bb5474a563a3fb",
      performed_by: 1,
      performer_name: PERFORMER,
      performed_at: "2026-08-07 20:23:44",
      ip_address: null,
    },
  ];
}

function renderModal(entries: AuditLogEntry[] = makeEntries()) {
  const result = render(
    <TranslationProvider module="Documents" initialTranslations={TRANSLATIONS}>
      <ComplianceHistoryModal
        isOpen
        title="TS-03_入力期限"
        fileVersions={[]}
        auditLog={entries}
        onClose={() => {}}
      />
    </TranslationProvider>,
  );
  // 既定はファイルバージョンタブなので変更履歴へ切り替える
  fireEvent.click(screen.getByText("変更履歴"));
  return result;
}

describe("ComplianceHistoryModal - 変更履歴", () => {
  it("列幅を固定して詳細列に幅を奪われないようにする", () => {
    const { container } = renderModal();
    const table = container.querySelector("table") as HTMLTableElement;
    expect(table.style.tableLayout).toBe("fixed");
  });

  it("操作者が1文字ずつ折り返されないようにする", () => {
    renderModal();
    const cell = screen.getAllByText(PERFORMER)[0];
    // break-all / anywhere だと「シ ス テ ム 管 理 者」になる
    expect(cell.style.wordBreak).toBe("keep-all");
  });

  it("操作者の列に十分な幅を与える", () => {
    const { container } = renderModal();
    const headers = Array.from(container.querySelectorAll("th"));
    const performerHeader = headers.find(
      (th) => th.textContent === "操作者",
    ) as HTMLTableCellElement;
    expect(
      Number.parseInt(performerHeader.style.width, 10),
    ).toBeGreaterThanOrEqual(140);
  });

  it("ModTracker 由来の復元も操作名で表示する", () => {
    renderModal();
    expect(screen.getByText("復元")).toBeInTheDocument();
    expect(screen.getByText("削除")).toBeInTheDocument();
    expect(screen.getByText("ダウンロード")).toBeInTheDocument();
  });

  it("記録元をまたいでIDが重複しても全件描画する", () => {
    const { container } = renderModal();
    // ヘッダー行を除いた行数
    const rows = container.querySelectorAll("tbody tr");
    expect(rows.length).toBe(4);
  });

  it("区切りの無い長い値は詳細列の中で折り返す", () => {
    const { container } = renderModal();
    const detailCells = Array.from(
      container.querySelectorAll("tbody td"),
    ).filter((td) =>
      td.textContent?.includes("7725cec5b2b85aef"),
    ) as HTMLTableCellElement[];
    expect(detailCells.length).toBeGreaterThan(0);
    expect(detailCells[0].style.overflowWrap).toBe("anywhere");
  });
});
