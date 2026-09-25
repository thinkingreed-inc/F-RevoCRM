import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { DocumentsListView } from "../DocumentsListView";
import { TranslationProvider } from "../../../contexts/TranslationContext";
import type { DocumentRecord } from "../types/documents";

/**
 * 一覧のメモ列はプレーンテキストで出す
 *
 * メモ（notecontent）はリッチテキストエディタが保存した HTML で、本文が空でも
 * `<p><br /></p>` が入る。そのまま描画するとタグが文字として並ぶため、
 * タグを落として要約したものを表示する。
 */

const TRANSLATIONS: Record<string, string> = {
  Title: "タイトル",
  LBL_NO_DOCUMENTS: "ドキュメントがありません",
};

function makeRecord(id: number, notecontent: string | null): DocumentRecord {
  return {
    id,
    title: `ドキュメント${id}`,
    filename: `doc${id}.pdf`,
    filetype: "application/pdf",
    filesize: 1024,
    filelocationtype: "I",
    folderid: 1,
    foldername: "フォルダ",
    assigned_user_id: "1",
    assigned_user_name: "admin",
    modifiedtime: "2026-09-16 10:00:00",
    createdtime: "2026-09-16 10:00:00",
    filedownloadcount: 0,
    filestatus: 1,
    fileversion: null,
    starred: false,
    notecontent,
    note_no: `DOC${id}`,
    download_url: "",
    compliance: null,
    can_edit: true,
  };
}

function renderList(records: DocumentRecord[]) {
  return render(
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
        selectedIds={[]}
        onSelectionChange={() => {}}
      />
    </TranslationProvider>,
  );
}

describe("DocumentsListView - メモ列", () => {
  it("HTML タグは表示しない", () => {
    renderList([makeRecord(1, "<p>請求書の<strong>控え</strong></p>")]);

    expect(screen.getByText("請求書の控え")).toBeTruthy();
    expect(screen.queryByText(/<p>/)).toBeNull();
  });

  it("中身が空のメモは「—」を出す", () => {
    const { container } = renderList([makeRecord(1, "<p><br /></p>")]);

    expect(container.textContent).not.toContain("<br");
    expect(container.textContent).toContain("—");
  });
});
