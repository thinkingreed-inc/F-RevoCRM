import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import { DocumentsListView } from "../DocumentsListView";
import { TranslationProvider } from "../../../contexts/TranslationContext";
import type { DocumentRecord } from "../types/documents";

/**
 * 参照のみのフォルダのドキュメントは一括操作させない（TS-08 S-41）
 *
 * フォルダの権限が「参照」だけの場合、削除・移動はサーバーが拒否する。
 * 選択できてしまうと押してから断られるため、チェックボックスを無効にする。
 */

const TRANSLATIONS: Record<string, string> = {
  LBL_SELECT_ALL: "すべて選択",
  LBL_DOCUMENT_READONLY:
    "このフォルダは参照のみのため、ドキュメントを変更できません",
  Title: "タイトル",
  LBL_NO_DOCUMENTS: "ドキュメントがありません",
};

function makeRecord(
  id: number,
  title: string,
  canEdit: boolean,
): DocumentRecord {
  return {
    id,
    title,
    filename: `${title}.pdf`,
    filetype: "application/pdf",
    filesize: 1024,
    filelocationtype: "I",
    folderid: canEdit ? 1 : 2,
    foldername: canEdit ? "編集可" : "参照のみ",
    assigned_user_id: "1",
    assigned_user_name: "admin",
    modifiedtime: "2026-08-24 10:00:00",
    createdtime: "2026-08-24 10:00:00",
    filedownloadcount: 0,
    filestatus: 1,
    fileversion: null,
    starred: false,
    notecontent: null,
    note_no: `DOC${id}`,
    download_url: "",
    compliance: null,
    can_edit: canEdit,
  };
}

function renderList(records: DocumentRecord[], selectedIds: number[] = []) {
  const onSelectionChange = vi.fn();
  const result = render(
    <TranslationProvider module="Documents" initialTranslations={TRANSLATIONS}>
      <DocumentsListView
        records={records}
        total={records.length}
        page={1}
        pageLimit={20}
        sort={{ field: "modifiedtime", order: "DESC" }}
        isLoading={false}
        folders={[]}
        selectedFolderId="all"
        onSortChange={() => {}}
        onPageChange={() => {}}
        onRecordClick={() => {}}
        onFolderClick={() => {}}
        selectedIds={selectedIds}
        onSelectionChange={onSelectionChange}
      />
    </TranslationProvider>,
  );
  return { ...result, onSelectionChange };
}

describe("DocumentsListView - 参照のみのドキュメント", () => {
  it("参照のみは選択できない", () => {
    renderList([
      makeRecord(1, "編集できる", true),
      makeRecord(2, "参照のみ", false),
    ]);
    expect(
      (screen.getByLabelText("編集できる") as HTMLInputElement).disabled,
    ).toBe(false);
    expect(
      (screen.getByLabelText("参照のみ") as HTMLInputElement).disabled,
    ).toBe(true);
  });

  it("参照のみのチェックボックスに理由を出す", () => {
    renderList([makeRecord(2, "参照のみ", false)]);
    expect(screen.getByLabelText("参照のみ").getAttribute("title")).toBe(
      TRANSLATIONS.LBL_DOCUMENT_READONLY,
    );
  });

  it("全選択は変更できる行だけを選ぶ", () => {
    const { onSelectionChange } = renderList([
      makeRecord(1, "編集できる", true),
      makeRecord(2, "参照のみ", false),
    ]);
    const selectAll = screen.getByLabelText("すべて選択") as HTMLInputElement;
    selectAll.click();
    expect(onSelectionChange).toHaveBeenCalledWith([1]);
  });

  it("変更できる行が無ければ全選択も無効", () => {
    renderList([makeRecord(2, "参照のみ", false)]);
    expect(
      (screen.getByLabelText("すべて選択") as HTMLInputElement).disabled,
    ).toBe(true);
  });

  it("can_edit が未指定なら従来どおり選択できる", () => {
    const record = makeRecord(3, "指定なし", true);
    delete record.can_edit;
    renderList([record]);
    expect(
      (screen.getByLabelText("指定なし") as HTMLInputElement).disabled,
    ).toBe(false);
  });
});
