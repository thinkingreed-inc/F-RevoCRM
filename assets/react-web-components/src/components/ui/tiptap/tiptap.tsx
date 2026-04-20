"use client";

import React, { useEffect, useState, useCallback, useMemo, useRef } from "react";
import Heading from "@tiptap/extension-heading";
import Underline from "@tiptap/extension-underline";
import Subscript from "@tiptap/extension-subscript";
import Superscript from "@tiptap/extension-superscript";
import TextAlign from "@tiptap/extension-text-align";
import Color from "@tiptap/extension-color";
import { TextStyle } from "@tiptap/extension-text-style";
import {
  useEditor,
  useEditorState,
  EditorContent,
} from "@tiptap/react";
import type { Editor } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import {
  Bold,
  Italic,
  Underline as UnderlineIcon,
  Strikethrough,
  Heading1,
  Heading2,
  Heading3,
  Pilcrow,
  LucideIcon,
  ChevronDown,
  Type,
  Highlighter,
  RemoveFormatting,
  List,
  ListOrdered,
  Indent,
  Outdent,
  Quote,
  ImagePlus,
  Code,
  TableIcon,
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuPortal,
  DropdownMenuTrigger,
} from "../dropdown-menu";

import {
  SpanHighlight,
  StyledTable,
  StyledTableCell,
  StyledTableHeader,
  FontSizeExtension,
  FONT_SIZES,
  IndentExtension,
  CellStyleExtension,
  SelectAllExtension,
  ResizableImage,
} from "./extensions";
import { Address } from "./extensions/address";
import { Big, Small } from "./extensions/big-small";
import { Kbd } from "./extensions/kbd";
import { BlockColor } from "./extensions/block-color";
import { FontFamilyExtension } from "./extensions/font-family";
import { DirAttribute } from "./extensions/dir-attribute";
import { StyledDiv } from "./extensions/styled-div";
import { StyledTableRow } from "./extensions/styled-table-row";
import { PageBreak } from "./extensions/page-break";
import { Anchor } from "./extensions/anchor";
import { Marker } from "./extensions/marker";
import { TableBubbleMenu } from "./components/table-bubble-menu";
import { ColorPicker } from "./components/color-picker";
import { TEXT_COLORS, HIGHLIGHT_COLORS } from "./constants";
import { formatHtml } from "./utils";
import "./tiptap.css";
import { useOptionalTranslation } from '../../../hooks/useTranslation';
import DOMPurify from 'dompurify';

/* ================================================================
 * メインコンポーネント
 * ================================================================ */

export interface TiptapProps {
  value?: string;
  onChange?: (event: { target: { name: string; value: string } }) => void;
  name?: string;
  className?: string;
  colors?: string[];
  isQuickCreate?: boolean;
}

const TiptapInitialValue = "<p></p>";

interface BlockTypeOption {
  label: string;
  value: string;
  icon: LucideIcon;
  onClick: () => void;
}

const Tiptap = React.forwardRef<HTMLDivElement, TiptapProps>(
  ({ value = "", onChange, name = "", className = "", colors, isQuickCreate }, ref) => {
    const { t } = useOptionalTranslation();
    const [content, setContent] = useState(value || TiptapInitialValue);
    const [sourceMode, setSourceMode] = useState(false);
    const [sourceHtml, setSourceHtml] = useState("");
    const [fontSizeDropdownMaxHeight, setFontSizeDropdownMaxHeight] = useState<number | undefined>(undefined);
    const fontSizeTriggerRef = useRef<HTMLButtonElement>(null);
    const portalContainerRef = useRef<HTMLDivElement>(null);
    const [isMobile, setIsMobile] = useState(
      () => typeof window !== 'undefined' && window.innerWidth < 768 && window.matchMedia('(pointer: coarse)').matches
    );
    const toolbarRef = useRef<HTMLDivElement>(null);
    const toolbarWrapperRef = useRef<HTMLDivElement>(null);
    const [showLeftGradient, setShowLeftGradient] = useState(false);
    const [showRightGradient, setShowRightGradient] = useState(true);
    const [removedTagsWarning, setRemovedTagsWarning] = useState<string[]>([]);
    const textPalette = colors && colors.length > 0 ? colors : TEXT_COLORS;
    const highlightPalette = HIGHLIGHT_COLORS;

    const editor = useEditor({
      extensions: [
        StarterKit.configure({ heading: false }),
        Underline,
        Subscript,
        Superscript,
        Heading.configure({ levels: [1, 2, 3, 4, 5, 6] }),
        TextAlign.configure({ types: ["heading", "paragraph", "address"] }),
        TextStyle,
        Color,
        FontSizeExtension,
        SpanHighlight.configure({ multicolor: true }),
        IndentExtension,
        CellStyleExtension,
        SelectAllExtension,
        ResizableImage.configure({ inline: true, allowBase64: true }),
        StyledTable.configure({ resizable: false }),
        StyledTableRow,
        StyledTableHeader,
        StyledTableCell,
        Address,
        Big,
        Small,
        Kbd,
        BlockColor,
        FontFamilyExtension,
        DirAttribute,
        StyledDiv,
        PageBreak,
        Anchor,
        Marker,
      ],
      content: content,
      editorProps: {
        attributes: { class: className },
        handleDrop: (view, event, _slice, moved) => {
          if (!moved && event.dataTransfer?.files?.length) {
            const file = event.dataTransfer.files[0];
            if (file.type.startsWith("image/")) {
              event.preventDefault();
              const reader = new FileReader();
              reader.onload = () => {
                const pos = view.posAtCoords({
                  left: event.clientX,
                  top: event.clientY,
                });
                if (pos && typeof reader.result === "string") {
                  const node = view.state.schema.nodes.image.create({
                    src: reader.result,
                  });
                  view.dispatch(view.state.tr.insert(pos.pos, node));
                }
              };
              reader.readAsDataURL(file);
              return true;
            }
          }
          return false;
        },
        handlePaste: (view, event) => {
          const items = event.clipboardData?.items;
          if (!items) return false;
          for (const item of items) {
            if (item.type.startsWith("image/")) {
              event.preventDefault();
              const file = item.getAsFile();
              if (!file) continue;
              const reader = new FileReader();
              reader.onload = () => {
                if (typeof reader.result === "string") {
                  const node = view.state.schema.nodes.image.create({
                    src: reader.result,
                  });
                  view.dispatch(view.state.tr.replaceSelectionWith(node));
                }
              };
              reader.readAsDataURL(file);
              return true;
            }
          }
          return false;
        },
      },
      onUpdate: ({ editor: e }: { editor: Editor }) => {
        // ZWS（ゼロ幅スペース）を除去してから onChange に渡す
        // ZWS はカーソルサイズ追従のためにフォントサイズ変更時に挿入されるが、
        // 保存コンテンツには含めない
        const html = e.getHTML().replace(/\u200B/g, "");
        const text = e.getText().trim().replace(/\u200B/g, "");
        setContent(e.getHTML()); // DOM は ZWS 付きのまま保持（カーソル描画のため）
        if (onChange) {
          onChange({
            target: { name, value: text === "" ? "" : html },
          });
        }
      },
    });

    // ブロックタイプの型定義
    type BlockType = "paragraph" | "h1" | "h2" | "h3";

    // ツールバーをトランザクション発生時にリアクティブに更新するための状態
    // カーソルのみ（テキスト未選択）状態でのフォントサイズ・スタイル変更をツールバーに反映する
    // また、太字・斜体などの isActive 状態もセレクターに含め、ボタンのアクティブ表示を即座に更新する
    const editorToolbarState = useEditorState({
      editor,
      selector: (ctx) => {
        const e = ctx.editor;
        if (!e) return {
          fontSize: "14px", textColor: "#000000", highlight: "",
          isBold: false, isItalic: false, isUnderline: false, isStrike: false,
          isBulletList: false, isOrderedList: false, isBlockquote: false,
          blockType: "paragraph" as BlockType,
        };
        return {
          // 既存フィールド: フォントサイズ・文字色・ハイライト
          fontSize: (e.getAttributes("textStyle")?.fontSize as string) || "14px",
          textColor: (e.getAttributes("textStyle")?.color as string) || "#000000",
          highlight: (e.getAttributes("highlight")?.color as string) || "",
          // 追加フィールド: 各書式ボタンのアクティブ状態
          isBold:        e.isActive("bold"),
          isItalic:      e.isActive("italic"),
          isUnderline:   e.isActive("underline"),
          isStrike:      e.isActive("strike"),
          isBulletList:  e.isActive("bulletList"),
          isOrderedList: e.isActive("orderedList"),
          isBlockquote:  e.isActive("blockquote"),
          blockType: (
            e.isActive("heading", { level: 1 }) ? "h1"
            : e.isActive("heading", { level: 2 }) ? "h2"
            : e.isActive("heading", { level: 3 }) ? "h3"
            : "paragraph"
          ) as BlockType,
        };
      },
    });

    const handleAction = useCallback(
      (e: React.MouseEvent, action: () => void) => {
        e.preventDefault();
        action();
      },
      []
    );

    useEffect(() => {
      if (editor && value !== editor.getHTML().replace(/\u200B/g, "")) {
        editor.commands.setContent(value || TiptapInitialValue, { emitUpdate: false });
        setContent(value || TiptapInitialValue);
      }
    }, [editor, value]);

    useEffect(() => {
      if (editor && !editor.getHTML()) {
        editor.commands.setContent(content);
      }
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [editor]);

    // isMobile: resize リスナー（150ms デバウンス）
    useEffect(() => {
      let timer: ReturnType<typeof setTimeout>;
      const handleResize = () => {
        clearTimeout(timer);
        timer = setTimeout(() => setIsMobile(window.innerWidth < 768 && window.matchMedia('(pointer: coarse)').matches), 150);
      };
      window.addEventListener('resize', handleResize);
      return () => {
        clearTimeout(timer);
        window.removeEventListener('resize', handleResize);
      };
    }, []);

    const blockTypeOptions: BlockTypeOption[] = useMemo(() => [
      {
        label: t('LBL_TIPTAP_PARAGRAPH'),
        value: "paragraph",
        icon: Pilcrow,
        onClick: () => editor?.chain().focus().setParagraph().run(),
      },
      {
        label: t('LBL_TIPTAP_HEADING1'),
        value: "h1",
        icon: Heading1,
        onClick: () =>
          editor?.chain().focus().toggleHeading({ level: 1 }).run(),
      },
      {
        label: t('LBL_TIPTAP_HEADING2'),
        value: "h2",
        icon: Heading2,
        onClick: () =>
          editor?.chain().focus().toggleHeading({ level: 2 }).run(),
      },
      {
        label: t('LBL_TIPTAP_HEADING3'),
        value: "h3",
        icon: Heading3,
        onClick: () =>
          editor?.chain().focus().toggleHeading({ level: 3 }).run(),
      },
    ], [editor, t]);

    const handleImageUpload = useCallback(() => {
      const input = document.createElement("input");
      input.type = "file";
      input.accept = "image/*";
      input.onchange = () => {
        const file = input.files?.[0];
        if (!file || !editor) return;
        const reader = new FileReader();
        reader.onload = () => {
          if (typeof reader.result === "string") {
            editor.chain().focus().setImage({ src: reader.result }).run();
          }
        };
        reader.readAsDataURL(file);
      };
      input.click();
    }, [editor]);

    const handleIndent = useCallback(() => {
      if (!editor) return;
      if (editor.isActive("listItem")) {
        editor.chain().focus().sinkListItem("listItem").run();
      } else {
        editor.chain().focus().indent().run();
      }
    }, [editor]);

    const handleOutdent = useCallback(() => {
      if (!editor) return;
      if (editor.isActive("listItem")) {
        editor.chain().focus().liftListItem("listItem").run();
      } else {
        editor.chain().focus().outdent().run();
      }
    }, [editor]);

    // リストトグル時に storedMarks（文字色・ハイライト・フォントサイズ）を保持する
    // toggleBulletList / toggleOrderedList はブロックレベルのトランザクションを発行し
    // ProseMirror の storedMarks をクリアするため、トグル前に値を取得して再適用する。
    // フォントサイズ再適用には setFontSize の代わりに setMark を直接使用する
    // （setFontSize は selection.empty 時に ZWS を挿入する副作用があるため）
    const handleBulletListToggle = useCallback(() => {
      if (!editor) return;
      const isEmptySelection = editor.state.selection.empty;
      const preColor     = isEmptySelection ? editor.getAttributes("textStyle")?.color as string | undefined : undefined;
      const preFontSize  = isEmptySelection ? editor.getAttributes("textStyle")?.fontSize as string | undefined : undefined;
      const preHighlight = isEmptySelection ? editor.getAttributes("highlight")?.color as string | undefined : undefined;
      const chain = editor.chain().focus().toggleBulletList();
      // storedMarks保持はカーソルのみ（空選択）の場合に限定
      // テキスト選択中は既存の混在書式を維持する
      if (preColor)     chain.setColor(preColor);
      if (preFontSize)  chain.setMark("textStyle", { fontSize: preFontSize });
      if (preHighlight) chain.setHighlight({ color: preHighlight });
      chain.run();
    }, [editor]);

    const handleOrderedListToggle = useCallback(() => {
      if (!editor) return;
      const isEmptySelection = editor.state.selection.empty;
      const preColor     = isEmptySelection ? editor.getAttributes("textStyle")?.color as string | undefined : undefined;
      const preFontSize  = isEmptySelection ? editor.getAttributes("textStyle")?.fontSize as string | undefined : undefined;
      const preHighlight = isEmptySelection ? editor.getAttributes("highlight")?.color as string | undefined : undefined;
      const chain = editor.chain().focus().toggleOrderedList();
      if (preColor)     chain.setColor(preColor);
      if (preFontSize)  chain.setMark("textStyle", { fontSize: preFontSize });
      if (preHighlight) chain.setHighlight({ color: preHighlight });
      chain.run();
    }, [editor]);

    const handleToolbarScroll = useCallback(() => {
      const el = toolbarRef.current;
      if (!el) return;
      setShowLeftGradient(el.scrollLeft > 0);
      setShowRightGradient(el.scrollLeft + el.clientWidth < el.scrollWidth - 1);
    }, []);

    useEffect(() => {
      handleToolbarScroll();
    }, [handleToolbarScroll]);

    const toggleSourceMode = useCallback(() => {
      if (!editor) return;
      if (!sourceMode) {
        // ソースモード突入時: ZWS（フォントサイズ変更時の副作用）を除去してから表示
        setSourceHtml(formatHtml(editor.getHTML().replace(/\u200B/g, '')));
      } else {
        // ソース内容をDOMPurifyでサニタイズしてからエディタのスキーマでフィルタリング
        const sanitized = DOMPurify.sanitize(sourceHtml);
        editor.commands.setContent(sanitized, { emitUpdate: false });
        // スキーマフィルタ済みHTML（ZWSも除去）
        const filteredHtml = editor.getHTML().replace(/\u200B/g, '');

        // タグ除去をTiptap正規化（属性順序変更等）と区別するためDOMParserでタグ名セット比較
        const parseBefore = new DOMParser().parseFromString(sourceHtml, 'text/html');
        const tagsBefore = new Set(
          Array.from(parseBefore.querySelectorAll('*')).map(el => el.tagName.toLowerCase())
        );
        const parseAfter = new DOMParser().parseFromString(filteredHtml, 'text/html');
        const tagsAfter = new Set(
          Array.from(parseAfter.querySelectorAll('*')).map(el => el.tagName.toLowerCase())
        );
        // DOMParserが自動生成するタグは除外
        const ignoredTags = new Set(['html', 'head', 'body']);
        const removedTags = [...tagsBefore].filter(t => !tagsAfter.has(t) && !ignoredTags.has(t));

        if (removedTags.length > 0) {
          // バックエンドサニタイズが主要軽減策。UXとして警告をコンソールとUIに表示
          console.warn('[Tiptap] 非サポートタグがエディタスキーマにより除去されました:', removedTags);
          setRemovedTagsWarning(removedTags);
        } else {
          setRemovedTagsWarning([]);
        }

        setContent(filteredHtml);
        if (onChange) {
          const tmp = document.createElement("div");
          tmp.innerHTML = filteredHtml;
          const text = (tmp.textContent || "").trim();
          onChange({
            target: { name, value: text === "" ? "" : filteredHtml },
          });
        }
      }
      setSourceMode((prev) => !prev);
    }, [editor, sourceMode, sourceHtml, onChange, name]);

    const handleSourceChange = useCallback(
      (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const html = e.target.value;
        setSourceHtml(html);
        // ソースモード中はローカル状態のみ更新
        // onChangeはソースモード終了時（toggleSourceMode）にスキーマフィルタ済みHTMLで発火する
      },
      // eslint-disable-next-line react-hooks/exhaustive-deps
      []
    );

    const FONT_SIZE_DROPDOWN_MIN_HEIGHT = 100; // 少なくとも2〜3項目を表示するための最低高

    const handleFontSizeOpenChange = (open: boolean) => {
      if (!isQuickCreate) return;
      if (!open) {
        setFontSizeDropdownMaxHeight(undefined);
        return;
      }
      const trigger = fontSizeTriggerRef.current;
      if (!trigger) return;

      const triggerRect = trigger.getBoundingClientRect();
      const scrollContainer = trigger.closest<HTMLElement>('.overflow-y-auto, .overflow-auto');
      const containerBottom = scrollContainer
        ? scrollContainer.getBoundingClientRect().bottom
        : window.innerHeight;

      const MARGIN = 8;
      const availableHeight = containerBottom - triggerRect.bottom - MARGIN;
      setFontSizeDropdownMaxHeight(Math.max(availableHeight, FONT_SIZE_DROPDOWN_MIN_HEIGHT));
    };

    const currentTextColor = editorToolbarState?.textColor ?? "#000000";
    const currentHighlight = editorToolbarState?.highlight ?? "";

    return (
      <>
      <div>
        {/* ===== Main Toolbar ===== */}
        <div ref={toolbarWrapperRef} style={{ position: 'relative' }}>
          <div
            ref={toolbarRef}
            role="toolbar"
            aria-label={t('LBL_TIPTAP_TOOLBAR')}
            onScroll={isMobile ? handleToolbarScroll : undefined}
            className={`tiptap-toolbar${isMobile ? ' tiptap-toolbar--mobile' : ''}`}
          >
          {/* Block type */}
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <button
                type="button"
                className="tiptap-block-select"
                onMouseDown={(e) => e.preventDefault()}
              >
                {(() => {
                  const opt = blockTypeOptions.find(
                    (o) => o.value === (editorToolbarState?.blockType ?? "paragraph")
                  );
                  if (!opt) return null;
                  const Icon = opt.icon;
                  return (
                    <>
                      <Icon size={14} />
                      <span>{opt.label}</span>
                      <ChevronDown size={10} />
                    </>
                  );
                })()}
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuPortal container={portalContainerRef.current}>
            <DropdownMenuContent
              side="bottom"
              align="start"
              avoidCollisions={false}
              collisionPadding={8}
              style={isMobile ? {
                width: `${toolbarWrapperRef.current?.offsetWidth ?? 0}px`,
                maxHeight: '60vh',
                overflowY: 'auto',
              } : undefined}
            >
              {blockTypeOptions.map((opt) => (
                <DropdownMenuItem
                  key={opt.value}
                  onMouseDown={(e) => e.preventDefault()}
                  onClick={opt.onClick}
                  style={{
                    backgroundColor:
                      (editorToolbarState?.blockType ?? "paragraph") === opt.value
                        ? "var(--muted, #f0f0f0)"
                        : undefined,
                  }}
                >
                  <opt.icon size={14} />
                  <span>{opt.label}</span>
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
            </DropdownMenuPortal>
          </DropdownMenu>

          {/* Font size */}
          <DropdownMenu modal={false} onOpenChange={!isMobile ? handleFontSizeOpenChange : undefined}>
            <DropdownMenuTrigger asChild>
              <button
                ref={fontSizeTriggerRef}
                type="button"
                className="tiptap-block-select"
                style={{ minWidth: "64px" }}
                onMouseDown={(e) => e.preventDefault()}
              >
                <span>
                  {editorToolbarState?.fontSize ?? "14px"}
                </span>
                <ChevronDown size={10} />
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuPortal container={portalContainerRef.current}>
            <DropdownMenuContent
              side="bottom"
              align="start"
              avoidCollisions={false}
              collisionPadding={8}
              style={isMobile ? {
                width: `${toolbarWrapperRef.current?.offsetWidth ?? 0}px`,
                maxHeight: '60vh',
                overflowY: 'auto',
              } : (isQuickCreate && fontSizeDropdownMaxHeight !== undefined
                  ? { maxHeight: `${fontSizeDropdownMaxHeight}px` }
                  : undefined)}
              onCloseAutoFocus={(e) => {
                // Radix UI のアクセシビリティ仕様によりフォーカスがトリガーに戻るため、
                // 意図的にエディターへフォーカスを戻す
                e.preventDefault();
                if (editor && !editor.isDestroyed) {
                  editor.view.dom.focus();
                }
              }}
            >
              {isMobile ? (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: 4, padding: 6 }}>
                  {FONT_SIZES.map((fs) => {
                    const cur = editorToolbarState?.fontSize ?? "14px";
                    return (
                      <DropdownMenuItem
                        key={fs.value}
                        onMouseDown={(e) => e.preventDefault()}
                        onClick={() =>
                          fs.value === "14px"
                            ? editor?.chain().focus().unsetFontSize().run()
                            : editor?.chain().focus().setFontSize(fs.value).run()
                        }
                        style={{
                          backgroundColor:
                            cur === fs.value
                              ? "var(--muted, #f0f0f0)"
                              : undefined,
                          justifyContent: 'center',
                          minHeight: '44px',
                          padding: '4px 2px',
                        }}
                      >
                        <span style={{ fontSize: '11px' }}>{fs.label}</span>
                      </DropdownMenuItem>
                    );
                  })}
                </div>
              ) : (
                FONT_SIZES.map((fs) => {
                  const cur = editorToolbarState?.fontSize ?? "14px";
                  return (
                    <DropdownMenuItem
                      key={fs.value}
                      onMouseDown={(e) => e.preventDefault()}
                      onClick={() =>
                        fs.value === "14px"
                          ? editor?.chain().focus().unsetFontSize().run()
                          : editor?.chain().focus().setFontSize(fs.value).run()
                      }
                      style={{
                        backgroundColor:
                          cur === fs.value
                            ? "var(--muted, #f0f0f0)"
                            : undefined,
                      }}
                    >
                      <span style={{ fontSize: fs.value, lineHeight: 1.2 }}>
                        {fs.label}
                      </span>
                    </DropdownMenuItem>
                  );
                })
              )}
            </DropdownMenuContent>
            </DropdownMenuPortal>
          </DropdownMenu>

          <div className="tiptap-separator" />

          {/* Text formatting */}
          <button
            type="button"
            className={`tiptap-btn ${editorToolbarState?.isBold ? "active" : ""}`}
            title={t('LBL_TIPTAP_BOLD')}
            aria-pressed={editorToolbarState?.isBold ?? false}
            onMouseDown={(e) =>
              handleAction(e, () =>
                editor?.chain().focus().toggleBold().run()
              )
            }
          >
            <Bold size={14} />
          </button>
          <button
            type="button"
            className={`tiptap-btn ${editorToolbarState?.isItalic ? "active" : ""}`}
            title={t('LBL_TIPTAP_ITALIC')}
            aria-pressed={editorToolbarState?.isItalic ?? false}
            onMouseDown={(e) =>
              handleAction(e, () =>
                editor?.chain().focus().toggleItalic().run()
              )
            }
          >
            <Italic size={14} />
          </button>
          <button
            type="button"
            className={`tiptap-btn ${editorToolbarState?.isUnderline ? "active" : ""}`}
            title={t('LBL_TIPTAP_UNDERLINE')}
            aria-pressed={editorToolbarState?.isUnderline ?? false}
            onMouseDown={(e) =>
              handleAction(e, () =>
                editor?.chain().focus().toggleUnderline().run()
              )
            }
          >
            <UnderlineIcon size={14} />
          </button>
          <button
            type="button"
            className={`tiptap-btn ${editorToolbarState?.isStrike ? "active" : ""}`}
            title={t('LBL_TIPTAP_STRIKETHROUGH')}
            aria-pressed={editorToolbarState?.isStrike ?? false}
            onMouseDown={(e) =>
              handleAction(e, () =>
                editor?.chain().focus().toggleStrike().run()
              )
            }
          >
            <Strikethrough size={14} />
          </button>

          <div className="tiptap-separator" />

          {/* Colors */}
          <ColorPicker
            icon={<Type size={14} />}
            title={t('LBL_TIPTAP_TEXT_COLOR')}
            clearLabel={t('LBL_TIPTAP_CLEAR')}
            currentColor={currentTextColor}
            palette={textPalette}
            columns={10}
            onSelect={(c) => editor?.chain().focus().setColor(c).run()}
            onClear={() => editor?.chain().focus().unsetColor().run()}
            portalContainer={portalContainerRef.current}
          />
          <ColorPicker
            icon={<Highlighter size={14} />}
            title={t('LBL_TIPTAP_HIGHLIGHT_COLOR')}
            clearLabel={t('LBL_TIPTAP_CLEAR')}
            currentColor={currentHighlight}
            palette={highlightPalette}
            columns={7}
            onSelect={(c) =>
              editor?.chain().focus().toggleHighlight({ color: c }).run()
            }
            onClear={() => editor?.chain().focus().unsetHighlight().run()}
            portalContainer={portalContainerRef.current}
          />

          <div className="tiptap-separator" />

          {/* Lists & indent */}
          <button
            type="button"
            className={`tiptap-btn ${editorToolbarState?.isBulletList ? "active" : ""}`}
            title={t('LBL_TIPTAP_BULLET_LIST')}
            aria-pressed={editorToolbarState?.isBulletList ?? false}
            onMouseDown={(e) => handleAction(e, handleBulletListToggle)}
          >
            <List size={14} />
          </button>
          <button
            type="button"
            className={`tiptap-btn ${editorToolbarState?.isOrderedList ? "active" : ""}`}
            title={t('LBL_TIPTAP_ORDERED_LIST')}
            aria-pressed={editorToolbarState?.isOrderedList ?? false}
            onMouseDown={(e) => handleAction(e, handleOrderedListToggle)}
          >
            <ListOrdered size={14} />
          </button>
          <button
            type="button"
            className="tiptap-btn"
            title={t('LBL_TIPTAP_INDENT_INCREASE')}
            onMouseDown={(e) => handleAction(e, handleIndent)}
          >
            <Indent size={14} />
          </button>
          <button
            type="button"
            className="tiptap-btn"
            title={t('LBL_TIPTAP_INDENT_DECREASE')}
            onMouseDown={(e) => handleAction(e, handleOutdent)}
          >
            <Outdent size={14} />
          </button>

          <div className="tiptap-separator" />

          {/* Blockquote */}
          <button
            type="button"
            className={`tiptap-btn ${editorToolbarState?.isBlockquote ? "active" : ""}`}
            title={t('LBL_TIPTAP_BLOCKQUOTE')}
            aria-pressed={editorToolbarState?.isBlockquote ?? false}
            onMouseDown={(e) =>
              handleAction(e, () =>
                editor?.chain().focus().toggleBlockquote().run()
              )
            }
          >
            <Quote size={14} />
          </button>

          {/* Image */}
          <button
            type="button"
            className="tiptap-btn"
            title={t('LBL_TIPTAP_INSERT_IMAGE')}
            onMouseDown={(e) => {
              e.preventDefault();
              handleImageUpload();
            }}
          >
            <ImagePlus size={14} />
          </button>

          {/* Table insert */}
          <button
            type="button"
            className="tiptap-btn"
            title={t('LBL_TIPTAP_INSERT_TABLE')}
            onMouseDown={(e) => {
              e.preventDefault();
              editor
                ?.chain()
                .focus()
                .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
                .run();
            }}
          >
            <TableIcon size={14} />
          </button>

          <div className="tiptap-separator" />

          {/* Clear formatting */}
          <button
            type="button"
            className="tiptap-btn"
            title={t('LBL_TIPTAP_CLEAR_FORMAT')}
            disabled={sourceMode}
            onMouseDown={(e) =>
              handleAction(e, () =>
                editor?.chain().focus().unsetAllMarks().run()
              )
            }
          >
            <RemoveFormatting size={14} />
          </button>

          <div className="tiptap-separator" />

          {/* Source mode */}
          <button
            type="button"
            className={`tiptap-btn ${sourceMode ? "active" : ""}`}
            title={t('LBL_TIPTAP_SOURCE_EDIT')}
            aria-pressed={sourceMode}
            onMouseDown={(e) => {
              e.preventDefault();
              toggleSourceMode();
            }}
          >
            <Code size={14} />
          </button>
          </div>
          {isMobile && showLeftGradient && (
            <div className="tiptap-toolbar-fade tiptap-toolbar-fade--left" />
          )}
          {isMobile && showRightGradient && (
            <div className="tiptap-toolbar-fade tiptap-toolbar-fade--right" />
          )}
        </div>

        {/* ===== Editor / Source ===== */}
        {sourceMode ? (
          <div className="tiptap-source-wrap" ref={ref}>
            <textarea
              className="tiptap-source-editor"
              value={sourceHtml}
              onChange={handleSourceChange}
              spellCheck={false}
            />
          </div>
        ) : (
          <div className="tiptap-editor-content" ref={ref}>
            <EditorContent editor={editor} />
            {editor && <TableBubbleMenu editor={editor} />}
          </div>
        )}
        {removedTagsWarning.length > 0 && (
          <div
            role="alert"
            className="tiptap-source-removed-warning"
          >
            {t('LBL_TIPTAP_SOURCE_TAG_REMOVED')}
          </div>
        )}
      </div>
      <div ref={portalContainerRef} style={{ position: 'relative' }} />
      </>
    );
  }
);

Tiptap.displayName = "Tiptap";

export default Tiptap;
