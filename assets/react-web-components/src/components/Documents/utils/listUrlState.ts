import type { FilterType } from "../types/documents";

/**
 * ドキュメント一覧の表示状態をURLと相互変換する
 *
 * フォルダやページを切り替えたときにURLへ反映しておくことで、
 * ブラウザの戻る／進むで直前の表示状態に戻れるようにする。
 */

/** URLに載せる一覧の表示状態 */
export interface DocumentsListUrlState {
  /** 選択中のフォルダ（"all" はすべてのドキュメント） */
  folderId: number | "all";
  /** ページ番号（1始まり） */
  page: number;
  /** 絞り込み（すべて／スター付き など） */
  filterType: FilterType;
  /** 検索キーワード */
  searchKeyword: string;
}

/** URLのパラメータ名（module / view など既存のパラメータは触らない） */
const PARAM = {
  folder: "doc_folder",
  page: "doc_page",
  filter: "doc_filter",
  keyword: "doc_q",
} as const;

/** 絞り込みとして受け付ける値 */
const FILTER_TYPES: FilterType[] = ["all", "starred", "recent"];

function toFilterType(value: string | null, fallback: FilterType): FilterType {
  return FILTER_TYPES.includes(value as FilterType)
    ? (value as FilterType)
    : fallback;
}

function toFolderId(
  value: string | null,
  fallback: number | "all",
): number | "all" {
  if (value === null || value === "") return fallback;
  if (value === "all") return "all";
  const folderId = Number(value);
  return Number.isInteger(folderId) && folderId > 0 ? folderId : fallback;
}

function toPage(value: string | null): number {
  const page = Number(value);
  return Number.isInteger(page) && page > 0 ? page : 1;
}

/**
 * URLから表示状態を読み取る
 *
 * @param search location.search（"?..." 形式）
 * @param fallback URLに指定が無い項目に使う既定値
 */
export function parseListUrlState(
  search: string,
  fallback: DocumentsListUrlState,
): DocumentsListUrlState {
  const params = new URLSearchParams(search);
  return {
    folderId: toFolderId(params.get(PARAM.folder), fallback.folderId),
    page: params.has(PARAM.page)
      ? toPage(params.get(PARAM.page))
      : fallback.page,
    filterType: toFilterType(params.get(PARAM.filter), fallback.filterType),
    searchKeyword: params.get(PARAM.keyword) ?? fallback.searchKeyword,
  };
}

/**
 * 表示状態を反映したURL（パス以降）を組み立てる
 *
 * 既定値（すべてのドキュメント・1ページ目・絞り込みなし・検索なし）の項目は
 * URLに載せず、余計なパラメータが残らないようにする。
 *
 * @param currentSearch location.search（module / view などを引き継ぐ）
 * @param state 表示状態
 * @param pathname location.pathname
 */
export function buildListUrl(
  currentSearch: string,
  state: DocumentsListUrlState,
  pathname = "",
): string {
  const params = new URLSearchParams(currentSearch);

  if (state.folderId === "all") {
    params.delete(PARAM.folder);
  } else {
    params.set(PARAM.folder, String(state.folderId));
  }

  if (state.page > 1) {
    params.set(PARAM.page, String(state.page));
  } else {
    params.delete(PARAM.page);
  }

  if (state.filterType && state.filterType !== "all") {
    params.set(PARAM.filter, state.filterType);
  } else {
    params.delete(PARAM.filter);
  }

  if (state.searchKeyword) {
    params.set(PARAM.keyword, state.searchKeyword);
  } else {
    params.delete(PARAM.keyword);
  }

  const query = params.toString();
  return query ? `${pathname}?${query}` : pathname;
}

/** 2つの表示状態が同じかどうか */
export function isSameListUrlState(
  a: DocumentsListUrlState,
  b: DocumentsListUrlState,
): boolean {
  return (
    a.folderId === b.folderId &&
    a.page === b.page &&
    a.filterType === b.filterType &&
    a.searchKeyword === b.searchKeyword
  );
}
