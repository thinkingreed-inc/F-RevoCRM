import React, { useState, useEffect, useCallback, useRef } from 'react';
import { X, Loader2, ChevronLeft, ChevronRight } from 'lucide-react';
import { Input } from './ui/input';
import { Button } from './ui/button';
import {
  Drawer,
  DrawerContent,
  DrawerHeader,
  DrawerTitle,
} from './ui/drawer';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from './ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from './ui/table';
import { cn } from '../lib/utils';

/**
 * 検索可能フィールドの型
 */
interface SearchableField {
  name: string;
  label: string;
  type: string;
  uitype: string;
  mandatory: boolean;
  picklistValues?: Array<{ value: string; label: string }>;
  ownerOptions?: {
    users: Array<{ value: string; label: string }>;
    groups: Array<{ value: string; label: string }>;
  };
}

/**
 * 検索結果レコードの型
 */
interface SearchRecord {
  id: string;
  label: string;
  module: string;
  fieldValues?: Record<string, string>;
}

/**
 * ページネーション情報の型
 */
interface PaginationInfo {
  page: number;
  limit: number;
  totalRecords: number;
  totalPages: number;
  hasNextPage: boolean;
  hasPrevPage: boolean;
}

/**
 * RecordSearchDrawerのProps
 */
export interface RecordSearchDrawerProps {
  /** Drawer表示状態 */
  open: boolean;
  /** Drawer表示状態変更コールバック */
  onOpenChange: (open: boolean) => void;
  /** 検索対象モジュール名 */
  moduleName: string;
  /** タイトル（例：「顧客企業を検索」） */
  title: string;
  /** レコード選択時のコールバック */
  onSelect: (record: SearchRecord) => void;
  /** 現在選択中のレコードID */
  selectedId?: string;
  /** 1ページあたりの件数（デフォルト: 20） */
  pageSize?: number;
}

/**
 * RecordSearchDrawer - フィールド条件検索付きレコード選択Drawer（リスト形式）
 * - ページネーション対応
 * - キーボードナビゲーション対応（矢印キー、Enter選択）
 */
export const RecordSearchDrawer: React.FC<RecordSearchDrawerProps> = ({
  open,
  onOpenChange,
  moduleName,
  title,
  onSelect,
  selectedId,
  pageSize = 20
}) => {
  // フィールド情報
  const [fields, setFields] = useState<SearchableField[]>([]);
  const [fieldsLoading, setFieldsLoading] = useState<boolean>(false);

  // 検索条件（フィールド名 -> 値）
  const [searchConditions, setSearchConditions] = useState<Record<string, string>>({});

  // 検索結果
  const [records, setRecords] = useState<SearchRecord[]>([]);
  const [recordsLoading, setRecordsLoading] = useState<boolean>(false);

  // ページネーション
  const [pagination, setPagination] = useState<PaginationInfo>({
    page: 1,
    limit: pageSize,
    totalRecords: 0,
    totalPages: 0,  // 0 = 未取得
    hasNextPage: false,
    hasPrevPage: false
  });

  // 総件数取得中フラグ
  const [countLoading, setCountLoading] = useState<boolean>(false);

  // キーボードナビゲーション用
  const [focusedIndex, setFocusedIndex] = useState<number>(-1);
  const tableRef = useRef<HTMLDivElement>(null);

  // デバウンス用タイマー
  const debounceRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * フィールド情報を取得
   */
  const fetchFields = useCallback(async () => {
    if (!moduleName) return;

    setFieldsLoading(true);
    try {
      const params = new URLSearchParams({
        module: moduleName,
        api: 'SearchRecords',
        include_fields: '1',
        limit: '0'
      });

      const response = await fetch(`?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      const fieldList = data.result?.fields || data.fields || [];
      // 検索に適したフィールドのみ（最初の6件）
      const searchableFields = fieldList.filter((f: SearchableField) =>
        ['string', 'text', 'email', 'phone', 'picklist', 'owner'].includes(f.type)
      ).slice(0, 6);
      setFields(searchableFields);
    } catch (err) {
      console.error('Failed to fetch fields:', err);
      setFields([]);
    } finally {
      setFieldsLoading(false);
    }
  }, [moduleName]);

  /**
   * レコード検索
   */
  const searchRecords = useCallback(async (conditions: Record<string, string>, page: number = 1) => {
    if (!moduleName) return;

    setRecordsLoading(true);
    setFocusedIndex(-1);
    try {
      const params = new URLSearchParams({
        module: moduleName,
        api: 'SearchRecords',
        include_fields: '1',
        limit: String(pageSize),
        page: String(page)
      });

      // フィールド条件がある場合
      const nonEmptyConditions = Object.fromEntries(
        Object.entries(conditions).filter(([_, v]) => v !== '')
      );

      if (Object.keys(nonEmptyConditions).length > 0) {
        params.append('search_fields', JSON.stringify(nonEmptyConditions));
      }

      const response = await fetch(`?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      const result = data.result || data;
      const recordList = result.records || [];

      setRecords(recordList);
      setPagination(prev => ({
        page: result.page || 1,
        limit: result.limit || pageSize,
        // 総件数はgetPageCountで取得するまで保持（リセット済みなら0のまま）
        totalRecords: prev.totalPages > 0 ? prev.totalRecords : 0,
        totalPages: prev.totalPages > 0 ? prev.totalPages : 0,
        hasNextPage: result.hasNextPage || false,
        hasPrevPage: result.hasPrevPage || false
      }));
    } catch (err) {
      console.error('Search failed:', err);
      setRecords([]);
      setPagination(prev => ({ ...prev, totalRecords: 0, totalPages: 0 }));
    } finally {
      setRecordsLoading(false);
    }
  }, [moduleName, pageSize]);

  /**
   * Drawer表示時にフィールド情報と初期レコードを取得
   */
  useEffect(() => {
    if (open && moduleName) {
      fetchFields();
      searchRecords({}, 1);
      setSearchConditions({});
    }
  }, [open, moduleName, fetchFields, searchRecords]);

  /**
   * 検索条件変更
   */
  const handleConditionChange = (fieldName: string, value: string) => {
    const newConditions = { ...searchConditions, [fieldName]: value };
    setSearchConditions(newConditions);

    // 検索条件変更時は総件数をリセット
    setPagination(prev => ({ ...prev, totalPages: 0, totalRecords: 0 }));

    // デバウンスして検索（ページを1にリセット）
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }
    debounceRef.current = setTimeout(() => {
      searchRecords(newConditions, 1);
    }, 500);
  };

  /**
   * 検索条件をクリア
   */
  const handleClearConditions = () => {
    setSearchConditions({});
    // 検索条件クリア時も総件数をリセット
    setPagination(prev => ({ ...prev, totalPages: 0, totalRecords: 0 }));
    searchRecords({}, 1);
  };

  /**
   * ページ変更
   */
  const handlePageChange = (newPage: number) => {
    searchRecords(searchConditions, newPage);
  };

  /**
   * 総件数を取得
   */
  const handleGetPageCount = useCallback(async () => {
    if (!moduleName || countLoading) return;

    setCountLoading(true);
    try {
      const params = new URLSearchParams({
        module: moduleName,
        api: 'SearchRecords',
        mode: 'getPageCount',
        limit: String(pageSize)
      });

      // フィールド条件がある場合
      const nonEmptyConditions = Object.fromEntries(
        Object.entries(searchConditions).filter(([_, v]) => v !== '')
      );

      if (Object.keys(nonEmptyConditions).length > 0) {
        params.append('search_fields', JSON.stringify(nonEmptyConditions));
      }

      const response = await fetch(`?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      const result = data.result || data;

      setPagination(prev => ({
        ...prev,
        totalRecords: result.numberOfRecords || 0,
        totalPages: result.page || 1
      }));
    } catch (err) {
      console.error('Failed to get page count:', err);
    } finally {
      setCountLoading(false);
    }
  }, [moduleName, pageSize, searchConditions, countLoading]);

  /**
   * レコード選択
   */
  const handleSelectRecord = (record: SearchRecord) => {
    onSelect(record);
    onOpenChange(false);
  };

  /**
   * キーボードナビゲーション
   */
  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (records.length === 0) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setFocusedIndex(prev => Math.min(prev + 1, records.length - 1));
        break;
      case 'ArrowUp':
        e.preventDefault();
        setFocusedIndex(prev => Math.max(prev - 1, 0));
        break;
      case 'Enter':
        e.preventDefault();
        if (focusedIndex >= 0 && focusedIndex < records.length) {
          handleSelectRecord(records[focusedIndex]);
        }
        break;
      case 'Escape':
        e.preventDefault();
        onOpenChange(false);
        break;
    }
  }, [records, focusedIndex, onOpenChange]);

  // フォーカス行をスクロールに追従
  useEffect(() => {
    if (focusedIndex >= 0 && tableRef.current) {
      const row = tableRef.current.querySelector(`[data-index="${focusedIndex}"]`);
      if (row) {
        row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    }
  }, [focusedIndex]);

  /**
   * フィールドタイプに応じた入力コンポーネントをレンダリング
   */
  const renderFieldInput = (field: SearchableField) => {
    const value = searchConditions[field.name] || '';

    // ピックリスト
    if (field.type === 'picklist' && field.picklistValues) {
      return (
        <Select
          value={value || '__empty__'}
          onValueChange={(v) => handleConditionChange(field.name, v === '__empty__' ? '' : v)}
        >
          <SelectTrigger className="h-[27.5px] text-[12.375px] w-[140px]">
            <SelectValue placeholder="--" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="__empty__" className="text-sm py-1.5">--</SelectItem>
            {field.picklistValues.map((opt) => (
              <SelectItem key={opt.value} value={opt.value || `__val_${opt.label}`} className="text-sm py-1.5">
                {opt.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      );
    }

    // 担当者
    if (field.type === 'owner' && field.ownerOptions) {
      const allOptions = [
        ...field.ownerOptions.users.map(u => ({ ...u, group: 'ユーザー' })),
        ...field.ownerOptions.groups.map(g => ({ ...g, group: 'グループ' }))
      ];
      return (
        <Select
          value={value || '__empty__'}
          onValueChange={(v) => handleConditionChange(field.name, v === '__empty__' ? '' : v)}
        >
          <SelectTrigger className="h-[27.5px] text-[12.375px] w-[140px]">
            <SelectValue placeholder="--" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="__empty__" className="text-sm py-1.5">--</SelectItem>
            {allOptions.map((opt) => (
              <SelectItem key={opt.value} value={String(opt.value)} className="text-sm py-1.5">
                {opt.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      );
    }

    // テキスト系（デフォルト）
    return (
      <Input
        type="text"
        value={value}
        onChange={(e) => handleConditionChange(field.name, e.target.value)}
        placeholder=""
        className="h-[27.5px] text-[12.375px] w-[140px]"
      />
    );
  };

  /**
   * ページネーションコントロール
   */
  const renderPagination = () => {
    // レコードがない場合は非表示
    if (records.length === 0) return null;

    const start = ((pagination.page - 1) * pagination.limit) + 1;
    const end = start + records.length - 1;
    const hasPagination = pagination.hasNextPage || pagination.hasPrevPage;

    return (
      <div className="flex items-center justify-between w-full">
        <div className="text-sm text-gray-600">
          {pagination.totalPages > 0 ? (
            // 総件数が取得済みの場合
            <span>
              {start} to {end} - {pagination.totalRecords}
            </span>
          ) : hasPagination ? (
            // ページネーションあり・総件数未取得の場合
            <span>
              {start} to {end} -{' '}
              <button
                type="button"
                onClick={handleGetPageCount}
                disabled={recordsLoading || countLoading}
                className="text-blue-600 hover:text-blue-800 hover:underline disabled:text-gray-400"
              >
                {countLoading ? '...' : '?'}
              </button>
            </span>
          ) : (
            // ページネーションなしの場合（全件表示）
            <span>{records.length} 件</span>
          )}
        </div>
        {hasPagination && (
          <div className="flex items-center gap-3">
            <Button
              variant="outline"
              size="sm"
              onClick={() => handlePageChange(pagination.page - 1)}
              disabled={!pagination.hasPrevPage || recordsLoading}
              className="h-11 px-5 text-sm border-[#eeeeee]"
            >
              <ChevronLeft className="w-4 h-4" />
              前へ
            </Button>
            <span className="text-sm text-gray-600 min-w-[80px] text-center">
              {pagination.totalPages > 0 ? (
                `${pagination.page} / ${pagination.totalPages}`
              ) : (
                `${pagination.page} ページ`
              )}
            </span>
            <Button
              variant="outline"
              size="sm"
              onClick={() => handlePageChange(pagination.page + 1)}
              disabled={!pagination.hasNextPage || recordsLoading}
              className="h-11 px-5 text-sm border-[#eeeeee]"
            >
              次へ
              <ChevronRight className="w-4 h-4" />
            </Button>
          </div>
        )}
      </div>
    );
  };

  return (
    <Drawer open={open} onOpenChange={onOpenChange}>
      <DrawerContent>
        <div
          className="mx-auto w-full h-[70vh] overflow-hidden flex flex-col relative"
          onKeyDown={handleKeyDown}
          tabIndex={0}
        >
          {/* 右上の閉じるボタン */}
          <button
            type="button"
            onClick={() => onOpenChange(false)}
            className="absolute top-2 right-4 size-[44px] flex items-center justify-center rounded-md bg-white border-0 hover:bg-gray-100 transition-colors z-20"
            aria-label="閉じる"
          >
            <X style={{ width: 20, height: 20 }} className="text-gray-500" />
          </button>

          <DrawerHeader className="pb-2">
            <DrawerTitle className="text-[30px] font-medium leading-[33px]">{title}</DrawerTitle>
          </DrawerHeader>

          <div className="px-4 flex flex-col flex-1 overflow-hidden">
            {fieldsLoading ? (
              <div className="flex items-center justify-center py-4">
                <Loader2 className="w-4 h-4 animate-spin text-gray-400" />
                <span className="ml-2 text-sm text-gray-500">読み込み中...</span>
              </div>
            ) : (
              <div className="flex-1 overflow-auto border border-[#eeeeee] rounded-md" ref={tableRef}>
                <Table className="text-[11.25px] leading-[17.5px]">
                  <TableHeader>
                    {/* ヘッダー行（ラベル＋検索入力を1行に統合） */}
                    <TableRow>
                      <TableHead className="w-[80px] h-[78px] text-center p-[6px] sticky top-0 bg-gray-50 z-10 align-middle border-[#eeeeee]">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={handleClearConditions}
                          className="h-[25px] text-[12.375px] px-[5px] border-[#eeeeee]"
                        >
                          <X className="w-3.5 h-3.5 mr-1" />
                          クリア
                        </Button>
                      </TableHead>
                      {fields.map((field) => (
                        <TableHead key={field.name} className="min-w-[120px] h-[78px] p-[6px] sticky top-0 bg-gray-50 z-10 align-middle border-[#eeeeee]">
                          <div className="flex flex-col gap-1 justify-center h-full">
                            <div className="text-[12.375px] font-normal">{field.label}</div>
                            {renderFieldInput(field)}
                          </div>
                        </TableHead>
                      ))}
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {recordsLoading ? (
                      <TableRow>
                        <TableCell colSpan={fields.length + 1} className="text-center py-4">
                          <div className="flex items-center justify-center">
                            <Loader2 className="w-4 h-4 animate-spin text-gray-400" />
                            <span className="ml-2 text-sm text-gray-500">検索中...</span>
                          </div>
                        </TableCell>
                      </TableRow>
                    ) : records.length > 0 ? (
                      records.map((record, index) => (
                        <TableRow
                          key={record.id}
                          data-index={index}
                          onClick={() => handleSelectRecord(record)}
                          className={cn(
                            'cursor-pointer transition-colors h-[35px]',
                            focusedIndex === index && 'bg-blue-100 ring-2 ring-blue-500 ring-inset',
                            focusedIndex !== index && selectedId === record.id && 'bg-blue-50',
                            focusedIndex !== index && selectedId !== record.id && 'hover:bg-gray-50'
                          )}
                        >
                          <TableCell className="text-center p-0 text-gray-500">
                            #{record.id}
                          </TableCell>
                          {fields.map((field) => (
                            <TableCell key={`${record.id}-${field.name}`} className="p-0">
                              {record.fieldValues?.[field.name] || '-'}
                            </TableCell>
                          ))}
                        </TableRow>
                      ))
                    ) : (
                      <TableRow>
                        <TableCell colSpan={fields.length + 1} className="text-center py-4 text-sm text-gray-500">
                          レコードがありません
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            )}

            {/* テーブル下の白背景エリア（ページネーション） */}
            <div className="h-20 flex-shrink-0 flex items-center px-4 w-full">
              {renderPagination()}
            </div>
          </div>

        </div>
      </DrawerContent>
    </Drawer>
  );
};

export default RecordSearchDrawer;
