import React, { useState, useMemo, useCallback } from "react";
import { useOptionalTranslation } from "../../hooks/useTranslation";
import type { Folder, FilterType } from "./types/documents";

interface DocumentsFolderTreeProps {
  folders: Folder[];
  totalCount: number;
  starredCount: number;
  selectedFolderId: number | "all";
  filterType: FilterType;
  onFolderSelect: (folderId: number | "all") => void;
  onFilterTypeChange: (filterType: FilterType) => void;
  onFolderCreate: (parentId: number) => void;
  onFolderEdit: (folder: Folder) => void;
  onFolderDelete: (folderId: number) => void;
}

interface FolderNode extends Folder {
  children: FolderNode[];
}

function buildTree(folders: Folder[]): FolderNode[] {
  const map = new Map<number, FolderNode>();
  const roots: FolderNode[] = [];

  folders.forEach((f) => map.set(f.id, { ...f, children: [] }));

  folders.forEach((f) => {
    const node = map.get(f.id)!;
    if (f.parent_id > 0 && map.has(f.parent_id)) {
      map.get(f.parent_id)!.children.push(node);
    } else {
      roots.push(node);
    }
  });

  return roots;
}

// 右クリックメニュー
interface ContextMenuState {
  x: number;
  y: number;
  folder: FolderNode;
}

const ContextMenu: React.FC<{
  state: ContextMenuState;
  onAddSubfolder: () => void;
  onEdit: () => void;
  onDelete: () => void;
  onClose: () => void;
}> = ({ state, onAddSubfolder, onEdit, onDelete, onClose }) => {
  const { t } = useOptionalTranslation();
  const isDefault = state.folder.name === "Default";
  const menuItemStyle: React.CSSProperties = {
    padding: "6px 16px",
    fontSize: 13,
    cursor: "pointer",
    whiteSpace: "nowrap",
    color: "#4A5568",
  };
  return (
    <>
      <div style={{ position: "fixed", inset: 0, zIndex: 999 }} onClick={onClose} />
      <div
        style={{
          position: "fixed",
          left: state.x,
          top: state.y,
          backgroundColor: "#fff",
          border: "1px solid #E2E8F0",
          borderRadius: 6,
          boxShadow: "0 4px 12px rgba(0,0,0,0.12)",
          zIndex: 1000,
          padding: "4px 0",
          minWidth: 160,
        }}
      >
        <div
          style={menuItemStyle}
          onClick={onAddSubfolder}
          onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = "#EDF2F7")}
          onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = "")}
        >
          {t('LBL_ADD_SUBFOLDER')}
        </div>
        {!isDefault && (
          <div
            style={menuItemStyle}
            onClick={onEdit}
            onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = "#EDF2F7")}
            onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = "")}
          >
            {t('LBL_EDIT_FOLDER')}
          </div>
        )}
        {!isDefault && (
          <div
            style={{ ...menuItemStyle, color: "#E53E3E" }}
            onClick={onDelete}
            onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = "#FFF5F5")}
            onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = "")}
          >
            {t('LBL_DELETE_FOLDER')}
          </div>
        )}
      </div>
    </>
  );
};

const FolderItem: React.FC<{
  node: FolderNode;
  depth: number;
  selectedId: number | "all";
  onSelect: (id: number) => void;
  onContextMenu: (e: React.MouseEvent, node: FolderNode) => void;
}> = ({ node, depth, selectedId, onSelect, onContextMenu }) => {
  const [expanded, setExpanded] = useState(true);
  const hasChildren = node.children.length > 0;
  const isSelected = selectedId === node.id;

  return (
    <div>
      <div
        onClick={() => onSelect(node.id)}
        onContextMenu={(e) => {
          e.preventDefault();
          onContextMenu(e, node);
        }}
        style={{
          display: "flex",
          alignItems: "center",
          gap: 4,
          padding: "4px 8px",
          paddingLeft: 8 + depth * 16,
          cursor: "pointer",
          borderRadius: 4,
          backgroundColor: isSelected ? "#EBF8FF" : "transparent",
          color: isSelected ? "#2B6CB0" : "#4A5568",
          fontSize: 13,
          fontWeight: isSelected ? 600 : 400,
          userSelect: "none",
        }}
      >
        {hasChildren && (
          <span
            onClick={(e) => {
              e.stopPropagation();
              setExpanded(!expanded);
            }}
            style={{
              width: 16,
              textAlign: "center",
              fontSize: 10,
              color: "#A0AEC0",
              cursor: "pointer",
            }}
          >
            {expanded ? "▼" : "▶"}
          </span>
        )}
        {!hasChildren && <span style={{ width: 16 }} />}
        <span style={{ flex: 1, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
          {node.name}
        </span>
        {node.count > 0 && (
          <span style={{ fontSize: 11, color: "#A0AEC0", flexShrink: 0 }}>
            {node.count}
          </span>
        )}
      </div>
      {expanded &&
        hasChildren &&
        node.children.map((child) => (
          <FolderItem
            key={child.id}
            node={child}
            depth={depth + 1}
            selectedId={selectedId}
            onSelect={onSelect}
            onContextMenu={onContextMenu}
          />
        ))}
    </div>
  );
};

export const DocumentsFolderTree: React.FC<DocumentsFolderTreeProps> = ({
  folders,
  totalCount,
  starredCount,
  selectedFolderId,
  filterType,
  onFolderSelect,
  onFilterTypeChange,
  onFolderCreate,
  onFolderEdit,
  onFolderDelete,
}) => {
  const { t } = useOptionalTranslation();
  const [searchQuery, setSearchQuery] = useState("");
  const [contextMenu, setContextMenu] = useState<ContextMenuState | null>(null);
  const tree = useMemo(() => buildTree(folders), [folders]);

  const filteredTree = useMemo(() => {
    if (!searchQuery) return tree;
    const q = searchQuery.toLowerCase();
    const filterNodes = (nodes: FolderNode[]): FolderNode[] =>
      nodes.reduce<FolderNode[]>((acc, node) => {
        const childResults = filterNodes(node.children);
        if (node.name.toLowerCase().includes(q) || childResults.length > 0) {
          acc.push({ ...node, children: childResults });
        }
        return acc;
      }, []);
    return filterNodes(tree);
  }, [tree, searchQuery]);

  const handleContextMenu = useCallback((e: React.MouseEvent, node: FolderNode) => {
    setContextMenu({ x: e.clientX, y: e.clientY, folder: node });
  }, []);

  const closeContextMenu = useCallback(() => setContextMenu(null), []);

  const smartFolderStyle = (active: boolean): React.CSSProperties => ({
    display: "flex",
    alignItems: "center",
    gap: 6,
    padding: "5px 8px",
    cursor: "pointer",
    borderRadius: 4,
    backgroundColor: active ? "#EBF8FF" : "transparent",
    color: active ? "#2B6CB0" : "#4A5568",
    fontSize: 13,
    fontWeight: active ? 600 : 400,
    userSelect: "none",
  });

  return (
    <div
      style={{
        width: 220,
        borderRight: "1px solid #E2E8F0",
        height: "100%",
        display: "flex",
        flexDirection: "column",
        overflow: "hidden",
      }}
    >
      {/* ヘッダー */}
      <div
        style={{
          padding: "12px 12px 8px",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
        }}
      >
        <span style={{ fontWeight: 700, fontSize: 14, color: "#2D3748" }}>
          {t('LBL_FOLDERS_SECTION')}
        </span>
        <button
          onClick={() => {
            const parentId = typeof selectedFolderId === "number" ? selectedFolderId : 0;
            onFolderCreate(parentId);
          }}
          title={t('LBL_ADD_FOLDER_TITLE')}
          style={{
            width: 24,
            height: 24,
            borderRadius: 4,
            border: "1px solid #E2E8F0",
            backgroundColor: "#fff",
            color: "#4299E1",
            fontSize: 16,
            lineHeight: 1,
            cursor: "pointer",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          +
        </button>
      </div>

      {/* フォルダ検索 */}
      <div style={{ padding: "0 8px 8px" }}>
        <input
          type="text"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder={t('LBL_SEARCH_FOLDERS')}
          style={{
            width: "100%",
            padding: "5px 8px",
            border: "1px solid #E2E8F0",
            borderRadius: 4,
            fontSize: 12,
            outline: "none",
            boxSizing: "border-box",
          }}
        />
      </div>

      {/* スクロール可能エリア */}
      <div style={{ flex: 1, overflowY: "auto", padding: "0 4px" }}>
        {/* 標準フォルダ */}
        <div style={{ marginBottom: 8 }}>
          <div style={{ padding: "4px 8px", fontSize: 11, color: "#A0AEC0", fontWeight: 600, textTransform: "uppercase" }}>
            {t('LBL_STANDARD_FOLDERS')}
          </div>
          <div
            onClick={() => { onFolderSelect("all"); onFilterTypeChange("all"); }}
            style={smartFolderStyle(filterType === "all" && selectedFolderId === "all")}
          >
            <span style={{ flex: 1 }}>{t('LBL_ALL_DOCUMENTS')}</span>
            <span style={{ fontSize: 11, color: "#A0AEC0" }}>{totalCount}</span>
          </div>
          <div
            onClick={() => { onFolderSelect("all"); onFilterTypeChange("starred"); }}
            style={smartFolderStyle(filterType === "starred")}
          >
            <span style={{ flex: 1 }}>{t('LBL_STARRED')}</span>
            <span style={{ fontSize: 11, color: "#A0AEC0" }}>{starredCount}</span>
          </div>
        </div>

        {/* ユーザーフォルダ */}
        <div>
          <div style={{ padding: "4px 8px", fontSize: 11, color: "#A0AEC0", fontWeight: 600, textTransform: "uppercase" }}>
            {t('LBL_FOLDERS_SECTION')}
          </div>
          {filteredTree.map((node) => (
            <FolderItem
              key={node.id}
              node={node}
              depth={0}
              selectedId={selectedFolderId}
              onSelect={(id) => { onFolderSelect(id); onFilterTypeChange("all"); }}
              onContextMenu={handleContextMenu}
            />
          ))}
        </div>
      </div>

      {/* 右クリックメニュー */}
      {contextMenu && (
        <ContextMenu
          state={contextMenu}
          onAddSubfolder={() => {
            onFolderCreate(contextMenu.folder.id);
            closeContextMenu();
          }}
          onEdit={() => {
            onFolderEdit(contextMenu.folder);
            closeContextMenu();
          }}
          onDelete={() => {
            if (window.confirm(t('LBL_CONFIRM_DELETE_FOLDER', contextMenu.folder.name))) {
              onFolderDelete(contextMenu.folder.id);
            }
            closeContextMenu();
          }}
          onClose={closeContextMenu}
        />
      )}
    </div>
  );
};
