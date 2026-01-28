import React, { useState, useEffect, useCallback, useRef } from 'react';
import { X, Loader2, ChevronLeft, ChevronRight, Check } from 'lucide-react';
import { Input } from './ui/input';
import { Button } from './ui/button';
import {
  Drawer,
  DrawerContent,
  DrawerHeader,
  DrawerTitle,
  DrawerFooter,
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
 * MultiSelectRecordSearchDrawerのProps
 */
export interface MultiSelectRecordSearchDrawerProps {
  /** Drawer表示状態 */
  open: boolean;
  /** Drawer表示状態変更コールバック */
  onOpenChange: (open: boolean) => void;
  /** 検索対象モジュール名 */
  moduleName: string;
  /** タイトル（例：「顧客企業を検索」） */
  title: string;
  /** レコード選択時のコールバック（複数選択結果を返す） */
  onSelect: (records: SearchRecord[]) => void;
  /** 現在選択中のレコード */
  selectedRecords?: SearchRecord[];
  /** 1ページあたりの件数（デフォルト: 20） */
  pageSize?: number;
}

/**
 * MultiSelectRecordSearchDrawer - 複数選択可能なフィールド条件検索付きレコード選択Drawer
 * - チェックボックスで複数レコードを選択
 * - ページネーション対応
 * - キーボードナビゲーション対応（矢印キー、Space選択）
 */
export const MultiSelectRecordSearchDrawer: React.FC<MultiSelectRecordSearchDrawerProps> = ({
  open,
  onOpenChange,
  moduleName,
  title,
  onSelect,
  selectedRecords = [],
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

  // 選択中のレコードID（Set for performance）
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());

  // 現在のページで選択中のレコード（完全なレコード情報を保持）
  const [localSelectedRecords, setLocalSelectedRecords] = useState<SearchRecord[]>([]);

  // ページネーション
  const [pagination, setPagination] = useState<PaginationInfo>({
    page: 1,
    limit: pageSize,
    totalRecords: 0,
    totalPages: 1,
    hasNextPage: false,
    hasPrevPage: false
  });

  // キーボードナビゲーション用
  const [focusedIndex, setFocusedIndex] = useState<number>(-1);
  const tableRef = useRef<HTMLDivElement>(null);

  // デバウンス用タイマー
  const debounceRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * Drawer表示時に選択中のレコードを初期化
   */
  useEffect(() => {
    if (open) {
      const ids = new Set(selectedRecords.map(r => r.id));
      setSelectedIds(ids);
      setLocalSelectedRecords([...selectedRecords]);
    }
  }, [open, selectedRecords]);

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
      setPagination({
        page: result.page || 1,
        limit: result.limit || pageSize,
        totalRecords: result.totalRecords || recordList.length,
        totalPages: result.totalPages || 1,
        hasNextPage: result.hasNextPage || false,
        hasPrevPage: result.hasPrevPage || false
      });
    } catch (err) {
      console.error('Search failed:', err);
      setRecords([]);
      setPagination(prev => ({ ...prev, totalRecords: 0, totalPages: 1 }));
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
    searchRecords({}, 1);
  };

  /**
   * ページ変更
   */
  const handlePageChange = (newPage: number) => {
    searchRecords(searchConditions, newPage);
  };

  /**
   * レコード選択トグル
   */
  const handleToggleRecord = (record: SearchRecord) => {
    const newIds = new Set(selectedIds);
    let newRecords = [...localSelectedRecords];

    if (newIds.has(record.id)) {
      // 選択解除
      newIds.delete(record.id);
      newRecords = newRecords.filter(r => r.id !== record.id);
    } else {
      // 選択追加
      newIds.add(record.id);
      newRecords.push(record);
    }

    setSelectedIds(newIds);
    setLocalSelectedRecords(newRecords);
  };

  /**
   * 全選択/全解除
   */
  const handleToggleAll = () => {
    const currentPageIds = new Set(records.map(r => r.id));
    const allSelected = records.every(r => selectedIds.has(r.id));

    const newIds = new Set(selectedIds);
    let newRecords = [...localSelectedRecords];

    if (allSelected) {
      // 現在のページを全解除
      records.forEach(r => {
        newIds.delete(r.id);
      });
      newRecords = newRecords.filter(r => !currentPageIds.has(r.id));
    } else {
      // 現在のページを全選択
      records.forEach(r => {
        if (!newIds.has(r.id)) {
          newIds.add(r.id);
          newRecords.push(r);
        }
      });
    }

    setSelectedIds(newIds);
    setLocalSelectedRecords(newRecords);
  };

  /**
   * 選択を確定
   */
  const handleConfirm = () => {
    onSelect(localSelectedRecords);
    onOpenChange(false);
  };

  /**
   * キャンセル
   */
  const handleCancel = () => {
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
      case ' ':
        e.preventDefault();
        if (focusedIndex >= 0 && focusedIndex < records.length) {
          handleToggleRecord(records[focusedIndex]);
        }
        break;
      case 'Enter':
        e.preventDefault();
        handleConfirm();
        break;
      case 'Escape':
        e.preventDefault();
        handleCancel();
        break;
    }
  }, [records, focusedIndex]);

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
          <SelectTrigger className="h-11 text-lg">
            <SelectValue placeholder="--" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="__empty__" className="text-lg py-3">--</SelectItem>
            {field.picklistValues.map((opt) => (
              <SelectItem key={opt.value} value={opt.value || `__val_${opt.label}`} className="text-lg py-3">
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
          <SelectTrigger className="h-11 text-lg">
            <SelectValue placeholder="--" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="__empty__" className="text-lg py-3">--</SelectItem>
            {allOptions.map((opt) => (
              <SelectItem key={opt.value} value={String(opt.value)} className="text-lg py-3">
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
        className="h-11 text-lg"
      />
    );
  };

  /**
   * ページネーションコントロール
   */
  const renderPagination = () => {
    if (pagination.totalPages <= 1) return null;

    return (
      <div className="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
        <div className="text-lg text-gray-600">
          {pagination.totalRecords}件中 {((pagination.page - 1) * pagination.limit) + 1}-{Math.min(pagination.page * pagination.limit, pagination.totalRecords)}件
        </div>
        <div className="flex items-center gap-3">
          <Button
            variant="outline"
            size="sm"
            onClick={() => handlePageChange(pagination.page - 1)}
            disabled={!pagination.hasPrevPage || recordsLoading}
            className="h-10 px-4 text-lg"
          >
            <ChevronLeft className="w-5 h-5" />
            前へ
          </Button>
          <span className="text-lg text-gray-600 min-w-[100px] text-center">
            {pagination.page} / {pagination.totalPages}
          </span>
          <Button
            variant="outline"
            size="sm"
            onClick={() => handlePageChange(pagination.page + 1)}
            disabled={!pagination.hasNextPage || recordsLoading}
            className="h-10 px-4 text-lg"
          >
            次へ
            <ChevronRight className="w-5 h-5" />
          </Button>
        </div>
      </div>
    );
  };

  // 現在のページで全選択状態かチェック
  const isAllSelected = records.length > 0 && records.every(r => selectedIds.has(r.id));
  const isSomeSelected = records.some(r => selectedIds.has(r.id)) && !isAllSelected;

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
            onClick={handleCancel}
            className="absolute top-2 right-4 p-2 rounded-md hover:bg-gray-100 transition-colors z-20"
            aria-label="閉じる"
          >
            <X className="w-5 h-5 text-gray-500" />
          </button>

          <DrawerHeader className="pb-2">
            <DrawerTitle className="text-xl">{title}</DrawerTitle>
            <div className="text-base text-blue-600 mt-1">
              選択中: {selectedIds.size}件
            </div>
          </DrawerHeader>

          <div className="px-4 flex flex-col flex-1 overflow-hidden">
            {fieldsLoading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
                <span className="ml-2 text-lg text-gray-500">読み込み中...</span>
              </div>
            ) : (
              <div className="flex-1 overflow-auto border rounded-md" ref={tableRef}>
                <Table className="text-lg">
                  <TableHeader className="sticky top-0 bg-white z-10">
                    {/* フィールドラベル行 */}
                    <TableRow className="bg-gray-50">
                      <TableHead className="w-[60px] py-3 !pl-4">
                        <button
                          type="button"
                          onClick={handleToggleAll}
                          className={cn(
                            'w-6 h-6 border-2 rounded flex items-center justify-center transition-colors',
                            isAllSelected && 'bg-blue-600 border-blue-600',
                            isSomeSelected && 'bg-blue-300 border-blue-300',
                            !isAllSelected && !isSomeSelected && 'border-gray-300 hover:border-blue-400'
                          )}
                          aria-label="全選択/全解除"
                        >
                          {(isAllSelected || isSomeSelected) && (
                            <Check className="w-4 h-4 text-white" />
                          )}
                        </button>
                      </TableHead>
                      <TableHead className="w-[100px] text-center py-3">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={handleClearConditions}
                          className="h-10 text-lg px-4"
                        >
                          <X className="w-5 h-5 mr-1" />
                          クリア
                        </Button>
                      </TableHead>
                      {fields.map((field) => (
                        <TableHead key={field.name} className="min-w-[160px] text-lg font-semibold py-3">
                          {field.label}
                        </TableHead>
                      ))}
                    </TableRow>
                    {/* 検索入力行 */}
                    <TableRow className="bg-gray-100">
                      <TableHead className="py-2 !pl-4"></TableHead>
                      <TableHead className="py-2">
                        <div className="text-lg text-gray-600 text-center font-normal">検索</div>
                      </TableHead>
                      {fields.map((field) => (
                        <TableHead key={`search-${field.name}`} className="py-2 px-2">
                          {renderFieldInput(field)}
                        </TableHead>
                      ))}
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {recordsLoading ? (
                      <TableRow>
                        <TableCell colSpan={fields.length + 2} className="text-center py-8">
                          <div className="flex items-center justify-center">
                            <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
                            <span className="ml-2 text-lg text-gray-500">検索中...</span>
                          </div>
                        </TableCell>
                      </TableRow>
                    ) : records.length > 0 ? (
                      records.map((record, index) => {
                        const isSelected = selectedIds.has(record.id);
                        return (
                          <TableRow
                            key={record.id}
                            data-index={index}
                            onClick={() => handleToggleRecord(record)}
                            className={cn(
                              'cursor-pointer transition-colors h-14',
                              focusedIndex === index && 'bg-blue-100 ring-2 ring-blue-500 ring-inset',
                              focusedIndex !== index && isSelected && 'bg-blue-50',
                              focusedIndex !== index && !isSelected && 'hover:bg-gray-50'
                            )}
                          >
                            <TableCell className="py-4 !pl-4">
                              <button
                                type="button"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  handleToggleRecord(record);
                                }}
                                className={cn(
                                  'w-6 h-6 border-2 rounded flex items-center justify-center transition-colors',
                                  isSelected && 'bg-blue-600 border-blue-600',
                                  !isSelected && 'border-gray-300 hover:border-blue-400'
                                )}
                                aria-label={isSelected ? '選択解除' : '選択'}
                              >
                                {isSelected && <Check className="w-4 h-4 text-white" />}
                              </button>
                            </TableCell>
                            <TableCell className="text-center text-lg py-4 text-gray-500">
                              #{record.id}
                            </TableCell>
                            {fields.map((field) => (
                              <TableCell key={`${record.id}-${field.name}`} className="py-4 text-lg">
                                {record.fieldValues?.[field.name] || '-'}
                              </TableCell>
                            ))}
                          </TableRow>
                        );
                      })
                    ) : (
                      <TableRow>
                        <TableCell colSpan={fields.length + 2} className="text-center py-8 text-lg text-gray-500">
                          レコードがありません
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            )}
          </div>

          {/* ページネーション */}
          {renderPagination()}

          <DrawerFooter className="pt-2">
            <div className="flex gap-3">
              <Button
                variant="outline"
                onClick={handleCancel}
                className="flex-1 h-12 text-lg"
              >
                キャンセル
              </Button>
              <Button
                onClick={handleConfirm}
                className="flex-1 h-12 text-lg"
              >
                選択を確定 ({selectedIds.size}件)
              </Button>
            </div>
          </DrawerFooter>
        </div>
      </DrawerContent>
    </Drawer>
  );
};

export default MultiSelectRecordSearchDrawer;
