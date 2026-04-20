import React, { useState, useEffect, useRef } from "react";
import type { Editor } from "@tiptap/react";
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore -- moduleResolution mismatch
import { BubbleMenu } from "@tiptap/react/menus";
import {
  Plus,
  Minus,
  Trash2,
  Paintbrush,
  ChevronDown,
  RemoveFormatting,
} from "lucide-react";
import { CELL_BG_COLORS } from "../constants";
import { useOptionalTranslation } from '../../../../hooks/useTranslation';

interface TableBubbleMenuProps {
  editor: Editor;
}

export const TableBubbleMenu = ({ editor }: TableBubbleMenuProps) => {
  const { t } = useOptionalTranslation();

  return (
    <BubbleMenu
      editor={editor}
      pluginKey="tableBubbleMenu"
      shouldShow={({ editor: e }: { editor: Editor }) => {
        return (
          e.isActive("table") &&
          !e.isActive("image") &&
          (e.isActive("tableCell") || e.isActive("tableHeader"))
        );
      }}
    >
      <div className="tiptap-table-bubble">
        {/* 行操作 */}
        <button
          type="button"
          className="tiptap-bubble-btn"
          title={t('LBL_TIPTAP_TABLE_ADD_ROW_BEFORE')}
          onMouseDown={(e) => {
            e.preventDefault();
            editor.chain().focus().addRowBefore().run();
          }}
        >
          <Plus size={12} />
          <span>{t('LBL_TIPTAP_TABLE_ROW_UP')}</span>
        </button>
        <button
          type="button"
          className="tiptap-bubble-btn"
          title={t('LBL_TIPTAP_TABLE_ADD_ROW_AFTER')}
          onMouseDown={(e) => {
            e.preventDefault();
            editor.chain().focus().addRowAfter().run();
          }}
        >
          <Plus size={12} />
          <span>{t('LBL_TIPTAP_TABLE_ROW_DOWN')}</span>
        </button>
        <button
          type="button"
          className="tiptap-bubble-btn"
          title={t('LBL_TIPTAP_TABLE_DELETE_ROW')}
          onMouseDown={(e) => {
            e.preventDefault();
            editor.chain().focus().deleteRow().run();
          }}
        >
          <Minus size={12} />
          <span>{t('LBL_TIPTAP_TABLE_ROW')}</span>
        </button>

        <span className="tiptap-bubble-sep" />

        {/* 列操作 */}
        <button
          type="button"
          className="tiptap-bubble-btn"
          title={t('LBL_TIPTAP_TABLE_ADD_COL_BEFORE')}
          onMouseDown={(e) => {
            e.preventDefault();
            editor.chain().focus().addColumnBefore().run();
          }}
        >
          <Plus size={12} />
          <span>{t('LBL_TIPTAP_TABLE_COL_LEFT')}</span>
        </button>
        <button
          type="button"
          className="tiptap-bubble-btn"
          title={t('LBL_TIPTAP_TABLE_ADD_COL_AFTER')}
          onMouseDown={(e) => {
            e.preventDefault();
            editor.chain().focus().addColumnAfter().run();
          }}
        >
          <Plus size={12} />
          <span>{t('LBL_TIPTAP_TABLE_COL_RIGHT')}</span>
        </button>
        <button
          type="button"
          className="tiptap-bubble-btn"
          title={t('LBL_TIPTAP_TABLE_DELETE_COL')}
          onMouseDown={(e) => {
            e.preventDefault();
            editor.chain().focus().deleteColumn().run();
          }}
        >
          <Minus size={12} />
          <span>{t('LBL_TIPTAP_TABLE_COL')}</span>
        </button>

        <span className="tiptap-bubble-sep" />

        {/* セル背景色 */}
        <CellColorPicker
          icon={<Paintbrush size={12} />}
          title={t('LBL_TIPTAP_TABLE_CELL_BG')}
          clearLabel={t('LBL_TIPTAP_CLEAR')}
          palette={CELL_BG_COLORS}
          columns={4}
          onSelect={(color) => editor.chain().focus().setCellBackground(color).run()}
          onClear={() => editor.chain().focus().setCellBackground(null).run()}
        />

        <span className="tiptap-bubble-sep" />

        {/* テーブル削除 */}
        <button
          type="button"
          className="tiptap-bubble-btn tiptap-bubble-btn-danger"
          title={t('LBL_TIPTAP_TABLE_DELETE')}
          onMouseDown={(e) => {
            e.preventDefault();
            editor.chain().focus().deleteTable().run();
          }}
        >
          <Trash2 size={12} />
        </button>
      </div>
    </BubbleMenu>
  );
};

/** バブルメニュー内のカラーピッカー（ポータル不使用・位置ずれ防止） */
interface CellColorPickerProps {
  icon: React.ReactNode;
  title: string;
  clearLabel: string;  // ハードコードされていた「クリア」を翻訳対応
  palette: string[];
  columns: number;
  onSelect: (color: string) => void;
  onClear: () => void;
}

const CellColorPicker = ({
  icon,
  title,
  clearLabel,
  palette,
  columns,
  onSelect,
  onClear,
}: CellColorPickerProps) => {
  const [open, setOpen] = useState(false);
  const wrapRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const handleClick = (e: MouseEvent) => {
      if (wrapRef.current && !wrapRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, [open]);

  return (
    <div ref={wrapRef} style={{ position: "relative" }}>
      <button
        type="button"
        className="tiptap-bubble-btn"
        title={title}
        aria-pressed={open}
        onMouseDown={(e) => {
          e.preventDefault();
          e.stopPropagation();
          setOpen((v) => !v);
        }}
      >
        {icon}
        <ChevronDown size={8} />
      </button>
      {open && (
        <div
          className="tiptap-cell-color-popup"
          onMouseDown={(e) => e.preventDefault()}
        >
          <div
            className="tiptap-color-grid"
            style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}
          >
            {palette.map((color) => (
              <button
                key={color}
                type="button"
                className="tiptap-color-swatch"
                style={{ backgroundColor: color }}
                title={color}
                onMouseDown={(e) => {
                  e.preventDefault();
                  onSelect(color);
                  setOpen(false);
                }}
              />
            ))}
          </div>
          <button
            type="button"
            className="tiptap-color-clear"
            onMouseDown={(e) => {
              e.preventDefault();
              onClear();
              setOpen(false);
            }}
          >
            <RemoveFormatting size={12} />
            <span>{clearLabel}</span>
          </button>
        </div>
      )}
    </div>
  );
};
